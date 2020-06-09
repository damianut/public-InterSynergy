<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\Security;

use App\Entity\User;
use App\Form\Type\User\ResetPassword\ResetPasswordType;
use App\Services\Validation\ValidationCustom;
use App\QuasiEntity\Resetter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Twig\Environment;

/**
 * Send e-mail with link which contains token to reset password,
 * if some requirements are fulfilled.
 */
class ProcessResetPasswordAccount extends ProcessAccount
{
  /**
   * Handling resetting password request.
   * 
   * At the beginning check, that email was passed to the method. 
   * If not, append message do $this->message about it.
   * 
   * Next set $email as class's property,
   * which serves while executing later methods.
   * 
   * Subsequently check that:
   * -e-mail has valid format
   * -user with this e-mail exists in database
   * -this user is enabled
   * -this user hasn't not used reset token.
   * 
   * Afterwards generate token, save this token in database and try send e-mail
   * with link contains this token to reset user's password.
   * 
   * At the end prepare template with message about successful or failed
   * reset request handling and return RedirectResponse with just prepared 
   * template.
   * 
   * @param  Request          $request  Current request
   * 
   * @return RedirectResponse $response Response about result of handling reset request
   */
  public function handleResetRequest(Request $request): RedirectResponse
  {
    do {
      $email = $request->request->get("resetter-email");
      if (!$email) {
        $this->flashSrvMsg->flashServicesMessage('app.resetter.email.blank');
        break;   
      }
      $validation = new ValidationCustom();
      $validation->validateEmail($email);
      $message = $validation->returnMessage();
      if ('' !== $message) {
        $this->flashBag->add('error', $message);
        break;
      }
      if (!$user = $this->userTable->userInDatabase($email)) {
        $this->flashSrvMsg->flashServicesMessage('app.resetter.email.404');
        break;
      }
      if (!$this->checkEnabled($user)) {
        break;
      }
      if (!$this->checkResetToken($user)) {
        break;
      }
      $token = Uuid::uuid4();
      $user->setResetToken($token);
      $user->setEntryUpdatingDate(new \DateTime());
      $this->entityManager->persist($user);
      $this->entityManager->flush();
      $title = $this->messages->get('app.resetter.email.title');
      $part = $this->messages->get('app.resetter.email.content');
      $url = $this->router->generate(
        'use-resetter-token',
        ['token' => $token],
        UrlGeneratorInterface::ABSOLUTE_URL
      );
      $content = $part.$url;
      if (!$this->emailer->sendEmail($email, $title, $content)) {
        $name = 'app.resetter.email.fail';
      } else {
        $name = 'app.resetter.email.sent';
      }
      $this->flashSrvMsg->flashServicesMessage($name);
    } while (false);
    $template = $this->router->generate('main-page');
    $response = new RedirectResponse($template);
    
    return $response;
  }
  
  /**
   * Handling password changing after click link with reset token from email
   * sended after reset request.
   * 
   * @param  Request                   $request  Current request
   * 
   * @return Response|RedirectResponse $response
   */
  public function handlePasswordChanging(Request $request)
  {
    $token = $request->query->get('token');
    $validation = new ValidationCustom();
    $validation->validateUuid($token);
    $message = $validation->returnMessage();
    do {
      if ('' !== $message) {
        $this->flashBag->add('error', $message);
        $template = $this->router->generate('main-page');
        $response = new RedirectResponse($template);
        break;
      }
      $repository = $this->entityManager->getRepository(User::class);
      $user = $repository->findOneBy(['resetToken' => $token]);
      if (!$user) {
        $this->flashBag->add(
            'error',
            $this->messages->get('app.resetter.user.token.404')
        );
        $template = $this->router->generate('main-page');
        $response = new RedirectResponse($template);
        break;
      }
      $resetter = new Resetter($token);
      $form = $this->formFactory->create(ResetPasswordType::class, $resetter);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        if ($this->changePassword($form->getData())) {
          $template = $this->router->generate('main-page');
          $response = new RedirectResponse($template);
          break;
        }
      }
      $template = $this->twig->render('resetter-page/index.html.twig', [
          'form' => $form->createView()
      ]);
      $response = new Response($template);
    } while (false);
    
    return $response;
  }
 
  /**
   * Check token and optionally change password
   * 
   * @param  Resetter $updatedResetter Object with token, new and repeated password
   * 
   * @return bool     $status          Result of password changing
   */
  private function changePassword(Resetter $updatedResetter): bool
  { 
    do {
      $validation = new ValidationCustom();
      $token = $updatedResetter->getToken();
      $validation->validateUuid($token);
      $message = $validation->returnMessage();
      if ('' !== $message) {
        break;   
      }
      $repository = $this->entityManager->getRepository(User::class);
      $user = $repository->findOneBy([
          'resetToken' => $token,
      ]);
      if (!$user) {
        $this->flashSrvMsg->flashServicesMessage('app.resetter.user.token.404');
        break;   
      }
      $newPassword = $updatedResetter->getNewPassword();
      $repeatPassword = $updatedResetter->getRepeatPassword();
      $validation->validatePassword($newPassword);
      $validation->validatePassword($repeatPassword);
      $message = $validation->returnMessage();
      if ('' !== $message) {
        break;   
      }
      if ($newPassword !== $repeatPassword) {
        $this->flashSrvMsg->flashServicesMessage('app.resetter.pwd.compare.fail');
        break;
      }
      $this->saveNewPassword($newPassword, $user);
      $status = true;
    } while (false);
    if ($message ?? false) {
      $this->flashBag->add('error', $message);
    }
    
    return $status ?? false;
  }
  
  /**
   * Check reset token.
   *
   * If exist return false and append message about it.
   * Else return true - it mean that we can create new token.
   * 
   * @param User $user User whose token will be checked
   * 
   * @return bool      Described above
   */
  private function checkResetToken(User $user): bool
  {
    if ($user->getResetToken()) {
      $this->flashSrvMsg->flashServicesMessage('app.resetter.token.exists');
      $status = false;
    }
    
    return $status ?? true; 
  }

  /**
   * Save new password of user in database.
   * 
   * @param string $password Password
   * @param User   $user     Instance of user
   */
  private function saveNewPassword(string $password, User $user)
  {
    $user->setResetToken(null);
    $user->setPassword($this->encoder->encodePassword(
        $user,
        $password
    ));
    $user->setEntryUpdatingDate(new \DateTime());
    $this->entityManager->persist($user);
    $this->entityManager->flush();
    $this->flashSrvMsg->flashServicesMessage('app.resetter.success');
  }
}
/*............................................................................*/