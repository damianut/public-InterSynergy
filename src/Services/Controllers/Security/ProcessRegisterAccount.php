<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\Security;

use App\Entity\User;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Handling registration account request
 */
class ProcessRegisterAccount extends ProcessAccount
{
  /**
   * Register Account Process.
   * 
   * @param Request $request Current request
   */
  public function registerAccountProcess(Request $request): void
  {
    $userData = $request->request->all();
    $this->setData($userData);
    if ($this->checkData()) {
      $user = $this->createAccount();
      $flash = $this->wpStatus->createCandidateMessage(
          $user->getId(),
          $user->getEmail()
      );
      $this->flashBag->add('info', $flash);
    }
  }
  
  /**
   * Create account in `digers`.`user` table.
   * 
   * @return User $user Created user
   */
  private function createAccount(): User
  {
    /**
     * Insert row about candidate to `intersynergy`.`wp_posts`.
     * In another words create instance of Candidate Custom Post Type 
     * in WordPress.
     */
    do {
      // Creating account in `intersynergy`.`user` table
      $user = new User();
      $user->setEnabled(false);
      $user->setFailedLogin(0);
      $user->setEmail($this->userData["user-email"]);
      $user->setPassword($this->encoder->encodePassword(
          $user,
          $this->userData["user-password"]
      ));
      $currentDateTime = new \DateTime();
      $user->setRegistrationDate($currentDateTime);
      $user->setEntryUpdatingDate($currentDateTime);
      $user->setBlockedConfirmationToken(Uuid::uuid4()->toString());
      $user->setRoles(["ROLE_USER"]);
      $this->entityManager->persist($user);
      $this->entityManager->flush();
      $email = $user->getEmail();
      $title = $this->messages->get('app.register.email.title');
      $part = $this->messages->get('app.register.acc.created');
      $url = $this->router->generate(
        'main-page',
        ['token' => $user->getBlockedConfirmationToken()],
        UrlGeneratorInterface::ABSOLUTE_URL
      );
      $content = $part.$url;
      if ($this->emailer->sendEmail($email, $title, $content)) {
        $name = 'app.register.acc.created.flash';
        $this->flashSrvMsg->flashServicesMessage($name);
      } else {
        $name = 'app.register.confirm.email.fail';
        $this->flashSrvMsg->flashServicesMessage($name);
      }
    } while (false);
    
    return $user;
  }

  /**
   * Check data provided by user.
   *
   * Check that $_POST contains only 2 elements and that is valid elements.
   *
   * Valid, i.e.:
   *   $_POST contains properly keys: 'user-email' and 'user-name'
   *   'user-email' value is correctly formated e-mail
   *   'user-name' value contains digits and letters only
   * 
   * @return bool $status True if data passed checks, false otherwise
   */
  private function checkData(): bool
  {
    do {
      /**
       * Check that nobody try to hack server by form.
       *
       * If hacker try to access '/login-page' directly, then
       * $this->userData = [$_POST['login-email'], $_POST['login-password']]
       * doesn't exists.
       *
       * I can detect it by counting elements of $this->userData in 1) and
       * checking keys of these elements in 2)
       *     1) $_POST should contains only 2 elements.
       *        Not less, not more.
       *
       *     2) Keys of $_POST should have properly values
       */
      if (count($this->userData) != 2) {
        $this->flashSrvMsg->flashServicesMessage('app.other.error');
        break;
      }
      if ($this->keys[0] != "user-email" || $this->keys[1] != "user-password") {
        $this->flashSrvMsg->flashServicesMessage('app.other.error');
        break;
      }
      /**
       * Now we know, that in $this->userData array are 2 element with valid
       * keys. It remains for us to check values of array, i.e. e-mail and
       * password. After successful validation, $this->message string will
       * still contains ''.
       * 
       * Flash message was added while executing `$this->checkProvidedData()`
       */
      if (!$this->checkProvidedData(
        $this->userData[$this->keys[0]],
        $this->userData[$this->keys[1]]
        )
      ) {
        break;   
      }
      /**
       * Check that e-mail passed by user exists in database only,
       * if provided e-mail and password is valid, i.e. when
       * $this->message === ''
       * 
       * If user with this e-mail exists, it's mean that e-mail is busy.
       */
      if ($this->userTable->userInDatabase($this->userData[$this->keys[0]])) {
        $this->flashSrvMsg->flashServicesMessage('app.register.email.busy');
        break;   
      }
      $status = true;
    } while (false);
    
    return $status ?? false;
  }
}
/*............................................................................*/
