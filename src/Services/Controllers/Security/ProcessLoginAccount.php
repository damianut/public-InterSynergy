<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\Security;

use App\Entity\User;
use App\Form\Type\User\UserPanel\UserPanelType;
use App\Services\Database\InfluenceUserTable;
use App\Services\DateTime\ProcessDateTime;
use App\Services\EntityActions\ChangeFailedLogin;
use App\Services\EntityActions\UpdateUser;
use App\Services\EntityActions\UserBanning;
use App\Services\EntityActions\UserPanel;
use App\Services\FilesHandling\FilesHandling;
use App\Services\Messages\EmailMessages;
use App\Services\Messages\FlashMessages;
use App\Services\TextProcess\TextProcess;
use App\Services\Validation\ValidationCustom;
use App\Services\WordPress\CandidateAccountMgmt;
use App\Services\WordPress\WpStatusHandling;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Twig\Environment;

/**
 * Handling login account request
 */
class ProcessLoginAccount extends ProcessAccount
{
  /**
   * Service for account activating.
   * 
   * @var ActivateAccount
   */
  private $activateAccount;

  /**
   * User changing failed login service.
   * 
   * @var ChangeFailedLogin
   */
  private $changeFailedLogin;
  
  /**
   * Service for uploading or deleting PDFs with CVs.
   * 
   * @var FilesHandling
   */
  private $filesHandling;
  
  /**
   * New session.
   * 
   * @var SessionInterface
   */
  private $session;
  
  /**
   * User updating service.
   * 
   * @var UpdateUser
   */
  private $updateUser;

  /**
   * User banning service.
   * 
   * @var UserBanning
   */
  private $banningUser;
  
  /**
   * Custom validator.
   * 
   * @var ValidationCustom
   */
  private $validator;

  /**
   * @param ActivateAccount              $activateAccount
   * @param CandidateAccountMgmt         $wpMgmt
   * @param ChangeFailedLogin            $changeFailedLogin
   * @param EmailMessages                $emailer
   * @param EntityManagerInterface       $entityManager  
   * @param Environment                  $twig
   * @param FilesHandling                $filesHandling   
   * @param FlashBagInterface            $flashBag       
   * @param FlashMessages                $flashSrvMsg
   * @param FormFactoryInterface         $formFactory
   * @param LoggerInterface              $logger         
   * @param InfluenceUserTable           $userTable   
   * @param ParameterBagInterface        $messages 
   * @param ProcessDateTime              $dateTime
   * @param SessionInterface             $session
   * @param TextProcess                  $text
   * @param UpdateUser                   $updateUser
   * @param UrlGeneratorInterface        $router
   * @param UserBanning                  $banningUser       
   * @param UserPasswordEncoderInterface $encoder 
   * @param ValidationCustom             $validator
   * @param WpStatusHandling             $wpStatus
   */
  public function __construct(
      ActivateAccount $activateAccount,
      CandidateAccountMgmt $wpMgmt,
      ChangeFailedLogin $changeFailedLogin,
      EmailMessages $emailer,
      EntityManagerInterface $entityManager,
      Environment $twig,
      FilesHandling $filesHandling,
      FlashBagInterface $flashBag,
      FlashMessages $flashSrvMsg,
      FormFactoryInterface $formFactory,
      LoggerInterface $logger,
      InfluenceUserTable $userTable,
      ParameterBagInterface $messages,
      ProcessDateTime $dateTime,
      SessionInterface $session,
      TextProcess $text,
      UpdateUser $updateUser,
      UrlGeneratorInterface  $router,
      UserBanning $banningUser,
      UserPasswordEncoderInterface $encoder,
      ValidationCustom $validator,
      WpStatusHandling $wpStatus
  ) {
    parent::__construct(
        $wpMgmt,
        $emailer,
        $entityManager,
        $twig,
        $flashBag,
        $flashSrvMsg,
        $formFactory,
        $logger,
        $userTable,  
        $messages,
        $dateTime,
        $text,
        $router,
        $encoder,
        $wpStatus
    );
    $this->activateAccount = $activateAccount;
    $this->banningUser = $banningUser;
    $this->changeFailedLogin = $changeFailedLogin;
    $this->filesHandling = $filesHandling;
    $this->session = $session;
    $this->validator = $validator;
    $this->updateUser = $updateUser;
  }
  
  /**
   * Process of SecurityController for "/login-account" Route.
   * 
   *   Set provided email and password in private properties.
   * 
   *   If user hasn't at lease one session's attributes - clear $_SESSION and 
   *   try to login user.
   *   
   *   If logging failed, prepare response with message about it,
   *   otherwise append session's attributes, to Session $this->session
   *   variable, which will be returned to LoginController::loginAccount.
   * 
   *   If admin has been logged in - redirect him to "admin-panel".
   * 
   *   Next I validate session's attributes: 'email' and 'loggedToken'.
   *   If it isn't valid it's mean, that user influenced on attributes in
   *   purpose of hacking server. So I clear session's attributes and redirect
   *   to 'main-page'.
   * 
   *   Subsequently authenticate template name session's attribute. If it isn't
   *   valid, so conclusion is the same as paragraph above.
   * 
   *   Afterwards compare logged token session's attribute with logged token
   *   of user from database; which email given as session's attribute and
   *   validated before.
   * 
   * @param Request   $request  Current request
   * 
   * @return Response $response Prepared response
   */
  public function loginControllerProcess(Request $request): Response
  {
    do {
      $userData = $request->request->all();
      $this->setData($userData);
      $hasSession = (
          $this->session->has('email') &&
          $this->session->has('loggedToken') &&
          $this->session->has('template')  
      );
      if ($hasSession) {
        $response = $this->handlingUserWithSession();
        if ($response) {
          break;
        }
        $email = $this->session->get('email');
        $loggedToken = $this->session->get('loggedToken');
        $template = $this->session->get('template');
      }
      if (!$hasSession) {
        $this->session->clear();
        if (!$this->loginUser()) {
          $url = $this->router->generate('main-page');
          $response = new RedirectResponse($url);
          break;
        }
        $email = $this->session->get('email');
        $loggedToken = $this->session->get('loggedToken');
        $template = $this->session->get('template'); 
      }
      if ("admin-page" === $template) {
        $url = $this->router->generate('admin-panel');
        $response = new RedirectResponse($url);
        break;
      }
      $user = $this->userTable->userInDatabase($email);
      /**
       * Check that user change name and/or surname. It's needed for
       * updating row in `intersynergy`.`wp_posts`.
       */
      $currentName = $user->getName();
      $currentSurname = $user->getSurname();
      $form = $this->formFactory->create(UserPanelType::class, $user);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        $user = $form->getData();
        $CVFilename = $user->getCVFilename();
        $retain = $form['retain']->getData();
        $cvFile = $form['CVFilename']->getData();
        $afterName = $user->getName();
        $afterSurname = $user->getSurname();
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
          $notice = $this->messages->get('app.pdf.404');
          $this->flashBag->add('notice', $notice);
        }
        /**
         * Try persist $user and catch error, if User provided data isn't
         * fulfilling constraints from `App\Entity\User.php`.
         */
        $user->setEntryUpdatingDate(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        /**
         * Update name and surname if was changed in WordPress tables.
         */
        if ($currentName != $afterName || $currentSurname != $afterSurname) {
          $allName = $afterName.' '.$afterSurname;
          $id = $user->getId();
          $flash = $this->wpStatus->updateCandidateMessage($allName, $id);
          $this->flashBag->add('info', $flash);
        }
        $this->flashSrvMsg->flashServicesMessage('app.login.update.success');
        $url = $this->router->generate('successful');
        $response = new RedirectResponse($url);
        break;
      }
      $renderedTemplate = $this->twig->render($template . '/index.html.twig', [
          'form' => $form->createView(),
          'filename' => $form->getData()->getCvFilename(),
      ]);
      $response = new Response();
      $response->setContent($renderedTemplate);
    } while (false);
    
    return $response;
  }

  /**
   * Logic of "/main-page" Route.
   * 
   * @param  Request                   $request  
   * 
   * @return Response|RedirectResponse $response
   */
  public function mainPageLogic(Request $request)
  {
    do {
      if (
          $this->session->has('email') &&
          $this->session->has('loggedToken') &&
          $this->session->has('template')
      ) {
        $response = $this->handlingUserWithSession();
        if (!$response) {
          $url = $this->router->generate('login-account');
          $response = new RedirectResponse($url);
        }
        break;
      }
      $token = $request->query->get('token');
      $activateMessage = $token ?
          $this->activateAccount->activateAccount($token) : '';
      $this->flashBag->add(
          'info',
          $activateMessage
      ); 
      $template = $this->twig->render("main-page/index.html.twig");
      $response = new Response($template);
    } while (false);
    
    return $response;
  }
  
  /**
   * Delete session and Return RedirectResponse to `main-page`.
   * 
   * @return RedirectResponse
   */
  public function deleteSession(): RedirectResponse
  {
    $email = $this->session->get('email');
    $user = $this->userTable->userInDatabase($email);
    $this->session->clear();
    if ($user) {
      $user->setLoggedToken(NULL);
      $this->entityManager->persist($user);
      $this->entityManager->flush();
      $message = $this->messages->get('app.logout.success');
    } else {
      $message = $this->messages->get('app.logout.non');
    }
    $this->flashBag->add('info', $message);
    $url = $this->router->generate('main-page');
    
    return new RedirectResponse($url);
  }
  
  /**
   * Handling Request from User with Session.
   * 
   * @return RedirectResponse|null $response
   */
  private function handlingUserWithSession(): ?RedirectResponse
  {
    do {
      $email = $this->session->get('email');
      $loggedToken = $this->session->get('loggedToken');
      $template = $this->session->get('template');
      $this->validator->validateEmail($email);
      $this->validator->validateUuid($loggedToken);
      $message = $this->validator->returnMessage();
      if ('' != $message) {
        $response = $this->deleteSession();
        break;   
      }
      if (!$this->authenticationOfTemplate($template)) {
        $response = $this->deleteSession();
        break;   
      }
      /** 
       * After comparing it's known that User really logged here.
       * It's hard to guess a token and email belongs to one user.
       * A hacker would have to create script to changing $_SESSION['email']
       * and $_SESSION['loggedToken'] and send Request to `login-account`.
       * I can defence from this attack by antiflooding mechanism
       * (I'm going to create it in future).
       */
      if (!$this->compareUuids($email, $loggedToken)) {
        $response = $this->deleteSession();
        break;   
      }
    } while (false);
    
    return $response ?? null;
  }
  
  /**
   * Returning the highest role of user. Roles have following order:
   *   .1. ROLE_ADMIN
   *   .2. ROLE_USER
   * User with 'ROLE_ADMIN' has access to `admin-panel`.
   * User with 'ROLE USER' has access to `user-panel`.
   * 
   * @param User $user User whose roles will be checked
   * 
   * @return string    If User has two roles, then his highest role is
   *                   ROLE_ADMIN, else if one, then it is ROLE_USER              
   */
  private function getHighestRole(User $user): string
  {
    $roles = $user->getRoles();
    return $roles[1] ?? $roles[0];
  }

  /**
   * Determine the name of the template to be rendered to the user.
   * 
   * @param string $role Highest role of user (ROLE_ADMIN or ROLE_USER)
   * 
   * @return string|null Template name or null
   */
  private function determineTemplate(string $role): ?string
  {
    if ($role === 'ROLE_ADMIN') {
      $template = "admin-page";
    } else if ($role === 'ROLE_USER') {
      $template = "user-page";
    }
    
    return $template ?? null;
  }

  /**
   * This method is called after successful authentication to reset failed login
   * counter and update User:loginDate property.
   * 
   * @param User       $user An user who will have updated data
   * 
   * @return bool            True if user was updated, false otherwise
   */
  private function successfulAuthentication(User $user): bool
  {
    $user->setFailedLogin(0);
    $user->setLoginDate(new \DateTime());
    if (!$this->updateUser->update($user)) {
      $this->flashSrvMsg->flashServicesMessage('app.login.update.fail');
      $result = false;
    } 
    
    return $result ?? true;
  }

  /**
   * This method is called after failed authentication to increase failed login
   * counter and resolve that user should be banned.
   * 
   * @param User $user A user who will have updated data about the number of
   *                   failed login attempts and who may be banned.
   */
  private function failedAuthentication(User $user)
  {
    /**
     * In this place User:failedLogin equals a maximum of 2,
     * because passed password in form is checked after
     * checking that user is enabled.
     *
     * So increase value of failed logins's counter.
     */
    $failedLoginAttempts = $user->getFailedLogin();
    $this->changeFailedLogin->change($user, ++$failedLoginAttempts);
    $limit = $this->messages->get('app.login.attempts');
    /**
     * If counter's value equals (or exceeds) number 3 –
     * ban user and send him message by email and/or browser,
     * that he has just been banned.
     */
    if ($user->getFailedLogin() >= $limit) {
      /**
       * Ban user
       */
      $this->banningUser->banningProcedure($user);
      /**
       * Send email with link to unblock account.
       * If message delivering fails, prepare message to user,
       * that he should send email to administrator about disabling,
       * if he want to enable account.
       * Else prepare message to user, that his account is disabled
       */
      $email = $user->getEmail();
      $title = $this->messages->get('app.login.acc.banned.msg.title');
      $part = $this->messages->get('app.login.acc.banned');
      $url = $this->router->generate(
        'main-page',
        ['token' => $user->getBlockedConfirmationToken()],
        UrlGeneratorInterface::ABSOLUTE_URL
      );
      $content = $part.$url;
      if ($this->emailer->sendEmail($email, $title, $content)) {
        $this->flashSrvMsg->flashServicesMessage('app.login.acc.banned.email');
      } else {
        $this->flashSrvMsg->flashServicesMessage('app.mail.fail');
      }
    }
    /**
     * Change entry updating date
     */
    $user->setEntryUpdatingDate(new \DateTime());
    if (!$this->updateUser->update($user)) {
      $this->flashSrvMsg->flashServicesMessage('app.update,err');
    }
  }

  /**
   * Method for token comparing.
   * 
   * @param string $email  Email of user
   * @param Uuid   $uuid   Token from session
   * 
   * @return bool  $status True if comparision succeeds, false otherwise
   */
  private function compareUuids(string $email, Uuid $uuid)
  {
    $user = $this->userTable->userInDatabase($email);
    $status = $user ? $user->getLoggedToken() === $uuid->__toString() :
        false;

    return $status;
  }
  
  /**
   * Authentication of template.
   * 
   * @param string $templateName Name of template
   * 
   * @return bool                True if authentication succeeded, false otherwise
   */
  private function authenticationOfTemplate(string $templateName): bool
  {
    return $templateName === "admin-page" || $templateName === "user-page"; 
  }
  
  /**
   * Check that loggedToken was Created less than 10 Minutes Ago.
   * 
   * User:loggedToken is created while login account. When logged user refresh
   * page, loggedToken from $_SESSION is compared with User:loggedToken from
   * database. After successful comparing, \DateTime() of comparing is saved to
   * User:loginDate. If 10 minutes from last comparing passed, user can login
   * account from another device or browser.
   * User:loggedToken (and User:loginDate) is changed, so if user refresh page
   * on browser/device which previously used to login account;
   * comparing fails – because token from $_SESSION isn't match a token from
   * database (it was changed while login account on another browser/device).
   * 
   * This mechanism prevents from login account more than once in the same
   * time and gives possibility to login account after 10 minutes from using
   * account (refreshing page) on the same or another browser/device,
   * if user forgot to logout.
   * 
   * @param User  $user 
   * 
   * @return bool $status True if loggedToken was created less than 10 minutes ago, false otherwise.
   */
  private function checkLoggedTokenTenMinutes(User $user): bool
  {
    do {
      if (!$user->getLoggedToken()) {
        $status = false;
        break;   
      }
      $datetime1 = $user->getLoginDate();
      $seconds = $this->dateTime->intervalSeconds($datetime1);
      if ($seconds >= 600) {
        $status = false;
      }
    } while (false);
    
    return $status ?? true;
  }
  
  /**
   * Create session.
   * 
   * Set email and template name to session variable, generate token and set
   * it to row with user data in database and set it to session variable.
   * 
   * @param string $templateName Name of template to render
   * @param User   $user         Instance of logged user
   */
  private function createSession(string $templateName, User $user): void
  {
    $this->session->set('email', $this->userData[$this->keys[0]]);
    $this->session->set('template', $templateName);
    $loggedToken = Uuid::uuid4();
    $user->setLoggedToken($loggedToken->__toString());
    $user->setLoginDate(new \DateTime());
    $this->entityManager->persist($user);
    $this->entityManager->flush();
    $this->session->set('loggedToken', $loggedToken);
  }

  /**
   * Login process.
   * 
   * Purpose of this method is to login user i.e.:
   * -create and save `loggedToken` and \DateTime() of token creating to
   *  database (in row belongs to logged user)
   * -save to $_SESSION email, template of user to render and `loggedToken`
   * 
   * if data from request fulfill some requirements.
   * 
   * 
   * Clear `$this->message` on the beginning.
   * 
   * Check that nobody try to hack server by form.
   *
   * If hacker try to access '/login-page' directly, then
   * $this->userData = [$_POST['login-email'], $_POST['login-password']]
   * doesn't exists.
   *
   * I can detect it by counting elements of $this->userData and
   * checking keys of these elements.
   *     1) $_POST should contains only 2 elements.
   *        Not less, not more.
   *
   *     2) Keys of $_POST should have properly values
   * 
   * Next check that email and password is valid.
   *
   * Subsequently check that user with passed email exists.
   * 
   * In the next step check, that user login account more than or equal to 10
   * minutes. If did it, `loggedToken` and \DateTime() of saving `loggedToken`
   * will be overwritten.
   * 
   * Afterwards check that account is enabled. If not reset `$this->message`
   * to '' because 'app.login_acc_disabled' message was appending two times
   * otherwise. I don't know why.
   * 
   * Then check passed password matchs password of this user from database.
   * If not, note in database failed login attempt.
   * 
   * Next update user entity and create new session.
   * 
   * @return bool $status Return true if login succeeded, false otherwise
   */
  private function loginUser(): bool
  {
    do {
      if (count($this->userData) != 2) {
        $this->flashSrvMsg->flashServicesMessage('app.other.error');
        break;
      } 
      if (
        $this->keys[0] != "login-email" ||
        $this->keys[1] != "login-password"
        ) {
        $this->flashSrvMsg->flashServicesMessage('app.other.error');
        break;
      }
      $email = $this->userData[$this->keys[0]];
      if (!$this->checkProvidedData(
              $email,
              $this->userData[$this->keys[1]],
              true
          )
      ) {
        break;   
      }
      if (!$user = $this->userTable->userInDatabase($email)) {
        break;
      }
      if ($this->checkLoggedTokenTenMinutes($user)) {
        $this->flashSrvMsg->flashServicesMessage('app.login.10');
        break;
      }
      if (!$this->checkEnabled($user)) {
        break;   
      }
      if (!$this->encodedPwdValidation($user)) {
        $this->failedAuthentication($user);
        break;
      }
      if (!$this->successfulAuthentication($user)) {
        break;   
      }
      $highestRole = $this->getHighestRole($user);
      $templateName = $this->determineTemplate($highestRole);
      if (!$templateName) {
        $this->flashSrvMsg->flashServicesMessage('app.role.403');
        break;
      }
      $this->createSession($templateName, $user);
      $status = true;
    } while (false);
    
    return $status ?? false;
  }
}
/*............................................................................*/