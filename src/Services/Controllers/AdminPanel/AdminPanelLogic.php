<?php

/**
 * This file is part of InterSynergy Project.
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\AdminPanel;

use App\Entity\User;
use App\Form\Type\User\AdminPanel\CreateUserType;
use App\Form\Type\User\AdminPanel\EditUserType;
use App\Services\Database\InfluenceUserTable;
use App\Services\FilesHandling\FilesHandling;
use App\Services\Messages\FlashMessages;
use App\Services\Validation\ValidationCustom;
use App\Services\WordPress\WpStatusHandling;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Twig\Environment;

/**
 * Logic of AdminPanelController.
 */
class AdminPanelLogic
{
  /**
   * Entity Manager.
   * 
   * @var EntityManagerInterface
   */
  private $em;
  
  /**
   * Rendering Twig Templates.
   * 
   * @var Environment
   */
  private $twig;
  
  /**
   * Service for uploading or deleting PDFs with CVs.
   * 
   * @var FilesHandling
   */
  private $filesHandling;
  
  /**
   * Service for getting Flash Messages.
   * 
   * @var FlashBag
   */
  private $flashBag;
  
  /**
   * Service for creating flash messages defined in 'config/services.yaml'
   * 
   * @var FlashMessages
   */
  private $flashSrvMsg;
  
  /**
   * Form Factory.
   * 
   * @var FormFactoryInterface
   */
  private $formFactory;
  
  /**
   * Service for getting Users's Data.
   * 
   * @var GetUsers
   */
  private $getUsers;
  
  /**
   * Perform actions on `intersynergy`.`user` table.
   * 
   * @var InfluenceUserTable
   */
  private $userTable;
  
  /**
   * Parameters from `config/services.yaml`.
   * 
   * @var ParameterBagInterface
   */
  private $messages;
  
  /**
   * Current Request.
   * 
   * @var Request
   */
  private $request;
  
  /**
   * Service for URL Generating.
   * 
   * @var RouterInterface
   */
  private $router;
  
  /**
   * Current Session.
   * 
   * @var Session
   */
  private $session;
 
  /**
   * Password encoder Selected by Symfony (configured in `security.yaml`).
   * 
   * @var UserPasswordEncoderInterface
   */
  private $encoder;
  
  /**
   * Performing actions in WordPress and returning results's messages
   * 
   * @var WpStatusHandling
   */
  private $wpStatus;
  
  /**
   * @param EntityManagerInterface       $em
   * @param Environment                  $twig
   * @param FilesHandling                $filesHandling
   * @param FlashMessages                $flashSrvMsg 
   * @param FormFactoryInterface         $formFactory
   * @param GetUsers                     $getUsers
   * @param InfluenceUserTable           $userTable
   * @param ParameterBagInterface        $messages
   * @param RequestStack                 $requestStack
   * @param RouterInterface              $router
   * @param UserPasswordEncoderInterface $encoder
   * @param WpStatusHandling             $wpStatus
   */
  public function __construct(
      EntityManagerInterface $em,
      Environment $twig,
      FilesHandling $filesHandling,
      FlashMessages $flashSrvMsg,
      FormFactoryInterface $formFactory,
      GetUsers $getUsers,
      InfluenceUserTable $userTable,
      ParameterBagInterface $messages,
      RequestStack $requestStack,
      RouterInterface $router,
      UserPasswordEncoderInterface $encoder,
      WpStatusHandling $wpStatus
  )
  { 
    $this->em = $em;
    $this->twig = $twig;
    $this->filesHandling = $filesHandling;
    $this->request = $requestStack->getCurrentRequest();
    $this->session = $this->request->getSession();
    $this->flashBag = $this->session->getFlashBag();
    $this->flashSrvMsg = $flashSrvMsg;
    $this->formFactory = $formFactory;
    $this->getUsers = $getUsers;
    $this->userTable = $userTable;
    $this->messages = $messages;
    $this->router = $router;
    $this->encoder = $encoder;
    $this->wpStatus = $wpStatus;
  }

  /**
   * Prepare Response for 'admin-panel' Page.
   * 
   * Check that admin try to browse this page.
   * Remove $_SESSION['edit_user_id'], if
   * Admin came here from `edit-user` route.
   * Then render page with data about all users.
   * 
   * @return Response|RedirectResponse $response
   */
  public function adminPanelResponse()
  {
    if (!$response = $this->authenticate()) {
      $this->session->remove('edit_user_id');
      $template = $this->twig->render('admin-page/index.html.twig', [
          'users' => $this->getUsers->getAll(),
      ]);
      $response = new Response($template);
    }
    
    return $response;
  }
  
  /**
   * Prepare Response for "/create-user" Page.
   * 
   * Check, who want to browse this page.
   * 
   * Then create Form to input data.
   * 
   * If admin submit valid data; check that e-mail is not used by another User.
   * If not, encrypt password and pass additional data to User entity.
   * Subsequently pass data to database and prepare response about successful
   * user creating.
   * 
   * Else render template with Form.
   */
  public function createUserResponse()
  {
    do {
      if ($response = $this->authenticate()) {
        break; 
      }
      $form = $this->formFactory->create(CreateUserType::class, new User());
      /**
       * All exceptions are catched, but only 'InvalidArgumentException' is 
       * handled because at that moment I detect while testing that only this
       * exception is throwed. If in future more error will be detected, they
       * will be easy handled by adding more else-if statements for all classes
       * of exceptions from 'Symfony\Component\PropertyAccess\Exception'
       * namespace.
       */
      try {
        $form->handleRequest($this->request);
      } catch (\Exception $e) {
        $exceptionNs = $this->messages->get('app.exception.property.access');
        $message = $e->getMessage();
        if ($exceptionNs . 'InvalidArgumentException' === get_class($e)) {
          $pwdMsg = $this->messages->get('app.exception.pwd');
          /**
           * Check that exception was throwed because password is null.
           */
          if ($pwdMsg === $message) {
            $templatePwdMsg = $this->messages->get('app.admin.tmpl.pwd');
            break;
          } else {
            $response = $this->flashRedirect($e->getMessage());
            break;
          }
        } else {
          $response = $this->flashRedirect($e->getMessage());
          break;
        }
      }
      if ($form->isSubmitted() && $form->isValid()) {
        $user = $form->getData();
        $email = $user->getEmail();
        if ($this->userTable->userInDatabase($email)) {
          $templateEmailMsg = $this->messages->get('app.admin.tmpl.email');
          break;
        }
        $role = $user->getRoles()[0];
        if ($role === "admin") {
          $user->setRoles(["ROLE_USER", "ROLE_ADMIN"]);
        } else if ($role === "user") {
          $user->setRoles(["ROLE_USER"]);
        } else {
          $templateRolesMsg = $this->messages->get('app.admin.tmpl.role');
          break;   
        }
        $rating = $user->getRating();
        if ($rating && !in_array($rating, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])) {
          $templateRatingMsg = $this->messages->get('app.admin.tmpl.rate');
          break;   
        }
        $plainPassword = $user->getPassword();
        $user->setPassword(
            $this->encoder->encodePassword($user, $plainPassword)
        );
        $user->setEnabled(true);
        $user->setFailedLogin(0);
        $currentDateTime = new \DateTime();
        $user->setRegistrationDate($currentDateTime);
        $user->setEntryUpdatingDate($currentDateTime);
        $user->setLoginDate($currentDateTime);
        /**
         * Save PDF File if Exists.
         */
        $cvFile = $form['CVFilename']->getData();
        if ($cvFile) {
          $this->filesHandling->savePDFFile($cvFile, $user);
        }
        $this->em->persist($user);
        $this->em->flush();
        $flash = $this->wpStatus->createCandidateMessage($user->getId(), $email);
        $done = $this->messages->get('app.admin.create');
        $flashAll = $flash."\n".$done;
        $response = $this->flashRedirect($flashAll, 'notice');
      }
    } while (false);
    if (!$response) {
      $template = $this->twig->render('create-user-page/index.html.twig', [
          'form' => $form->createView(),
          'filename' => $form->getData()->getCvFilename(),
          'pwdMsg' => $templatePwdMsg ?? null,
          'emailMsg' => $templateEmailMsg ?? null,
          'rolesMsg' => $templateRolesMsg ?? null,
          'ratingMsg' => $templateRatingMsg ?? null,
      ]);
      $response = new Response($template); 
    }
    
    return $response;
  }

  /**
   * Prepare Response for 'edit-user' Page.
   * 
   * Check that admin try to browse this page by ::authenticate method.
   * 
   * If admin hasn't ID of user in $_SESSION, get ID from $_REQUEST which was
   * created after clicking 'EDYTUJ' button on "/admin-panel". If ID not exists
   * in response or is less than 0; create flash about error. Else save this ID
   * in $_SESSION.
   * Else if admin has ID of user in $_SESSION, valid ID.
   * 
   * Then retrieve User by ID using Doctrine.
   * 
   * Subsequently create Form which depends on taken User.
   * 
   * Afterwards handle Request. If admin submitted valid data about User save
   * it in `intersynergy`.`user` table.
   * 
   * On the end redirect to 'admin-panel' to display created flash messages
   * or render page with form created to input data.
   * 
   * @return Response|RedirectResponse $response
   */
  public function editUserResponse()
  {
    do {
      if ($response = $this->authenticate()) {
        break; 
      }
      if (!$this->session->has('edit_user_id')) {
        $id = $this->retrieveId('edit_user_id');
        if (!$id) {
          $response = $this->flashRedirect('Nie podano poprawnego ID.');
          break;   
        }
        $this->session->set('edit_user_id', $id);
      } else {
        $id = $this->session->get('edit_user_id');
        $id = \intval($id);
        if (!\is_int($id) || $id == 0) {
          $response = $this->flashRedirect('Brak poprawnego ID w sesji.');
          break; 
        } 
      }
      if (!$user = $this->userTable->retrieveUser($id)) {
        $response = $this->flashRedirect('Brak osoby o podanym ID.');
        break;
      }
      $encrypted = $user->getPassword();
      $roles = $user->getRoles();
      /**
       * Check that user change name and/or surname. It's needed for
       * updating row in `intersynergy`.`wp_posts`.
       */
      $currentName = $user->getName();
      $currentSurname = $user->getSurname();
      $form = $this->formFactory->create(EditUserType::class, $user);
      $form->handleRequest($this->request);    
      if ($form->isSubmitted() && $form->isValid()) {
        $editedUser = $form->getData();
        $editedRoles = $form->get('roles')->getNormData();
        $afterName = $editedUser->getName();
        $afterSurname = $editedUser->getSurname();
        /**
         * All objects from `intersynergy`.`user` must contains at least one
         * role: ROLE_USER. Admin can only add ROLE_ADMIN role to roles of user.
         * But Admin didn't choose roles in form, $form->get('roles') is an
         * empty array. To prevent saving empty array to `intersynergy`.user`,
         * original value is saved in $editedUser (User Entity).
         */
        if (count($editedRoles) == 0) {
          $editedUser->setRoles($roles); 
        }
        $newPassword = $editedUser->getPassword();
        if ('n' === $newPassword) {
          $editedUser->setPassword($encrypted);
          unset($encrypted);
        } else {
          $validator = new ValidationCustom();
          $validator->validatePassword($newPassword);
          $message = $validator->returnMessage();
          if ('' !== $message) {
            $templatePwdMsg = $message;
            break;   
          }
          $editedUser->setPassword($this->encoder->encodePassword(
              $editedUser,
              $editedUser->getPassword()
          ));
        }
        /**
         * Save PDF File if Exists.
         */
        $retain = $form['retain']->getData();
        $CVFilename = $user->getCVFilename();
        $cvFile = $form['CVFilename']->getData();
        /**
         * Handle 3 possibilities:
         * -User unchecked checkbox near upload file button and has CV in db
         * -User didn't uncheck this checkbox and sent file by input[type=file]
         * -User unchecked checkbox and hasn't CV in db 
         */
        if (!$retain && $CVFilename) {
          $this->filesHandling->removePDFFile($user);
        } else if ($retain && $cvFile) {
          $this->filesHandling->savePDFFile($cvFile, $user);
        } else if (!$retain && !$CVFilename) {
          $flash = $this->messages->get('app.pdf.404');
          $this->flashBag->add('notice', $flash);
        }
        /**
         * Try persist $user and catch error, if User provided data isn't
         * fulfilling constraints from `App\Entity\User.php`.
         */
        $user->setEntryUpdatingDate(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();
        /**
         * Update name and surname if was changed.
         */
        if ($currentName != $afterName || $currentSurname != $afterSurname) {
            $nameAndSurname = $afterName.' '.$afterSurname;
            $flash = $this->wpStatus->updateCandidateMessage(
                $nameAndSurname,
                $editedUser->getId()
            );
        } 
        if ($this->flashBag->has('pdfError') || ($flash ?? false)) {
            $name = 'app.data.fail';
        } else {
            $name = 'app.data';
        }
        $sumMessage = $this->messages->get($name);
        $flashMessage = ($flash ?? '').' '.$sumMessage;
        $this->flashBag->add(
            'notice',
            $flashMessage
        );
        $this->session->remove('edit_user_id');
        $response = new RedirectResponse(
            $this->router->generate('admin-panel')
        );
      }
    } while (false);
    if (!$response) {
      $template = $this->twig->render('edit-user-page/index.html.twig', [
        'form' => $form->createView(),
        'filename' => $form->getData()->getCvFilename(),
        'pwdMsg' => $templatePwdMsg ?? null,
        'emailMsg' => $templateEmailMsg ?? null,
        'rolesMsg' => $templateRolesMsg ?? null,
        'ratingMsg' => $templateRatingMsg ?? null,
      ]);
      $response = new Response($template);   
    }
    
    return $response;
  }

  /**
   * Prepare Response for "/delete-user" Page.
   * 
   * Check who want to browse this page.
   * Then retrieve ID and User, who has this ID.
   * At the end remove User and render page with message about result.
   */
  public function deleteUserResponse()
  {
    do {
      if ($response = $this->authenticate()) {
        break; 
      }
      if (!$id = $this->retrieveId('delete_user_id')) {
        $response = $this->flashRedirect('Nie podano poprawnego ID.');
        break;   
      }
      if (!$user = $this->userTable->retrieveUser($id)) {
        $response = $this->flashRedirect('Brak użytkownika o podanym ID.');
        break;
      }
      $pdfName = $user->getCVFilename();
      if ($pdfName) {
        $this->filesHandling->removePDFFile($user);   
      }
      $this->em->remove($user);
      $this->em->flush();
      $flash = $this->wpStatus->deleteCandidateMessage($id);
      $response = $this->flashRedirect($flash, 'notice');
    } while (false);
    if (!$response) {
      $response = new RedirectResponse($this->router->generate('admin-panel'));
    }

    return $response;
  }

  /**
   * Check that Admin try to browse this Page.
   * 
   * @return RedirectResponse|null $response
   */
  private function authenticate(): ?RedirectResponse
  {
    if ('admin-page' !== $this->session->get('template')) {
      $response = $this->flashRedirect(
          $this->messages->get('app.admin.403'),
          'auth_error',
          'main-page');
    } 

    return $response ?? null;
  }
  
  /**
   * Create flash and redirect.
   * 
   * @param  string           $msgContent Content of message
   * @param  string           $flashType  Type of flash message
   * @param  string           $route      Route to which Admin should be redirect
   * 
   * @return RedirectResponse $response   
   */
  private function flashRedirect(string $message, string $flashType = 'error', string $route = 'admin-panel'): RedirectResponse
  {
    $this->flashBag->add(
        $flashType,
        $message);
        
    return new RedirectResponse($this->router->generate($route));  
  }
  
  /**
   * Retrieve ID from Request.
   * 
   * @param  string   $key Key of value from Request
   * 
   * @return int|null $id
   */
  private function retrieveId(string $key): ?int
  {
    $id = $this->request->request->get($key);
    $id = intval($id);
    if ($id <= 0) {
      $id = null;
    }
    
    return $id;
  }
}
/*............................................................................*/