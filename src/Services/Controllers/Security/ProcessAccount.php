<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\Security;

use App\Entity\User;
use App\Services\Database\InfluenceUserTable;
use App\Services\DateTime\ProcessDateTime;
use App\Services\Messages\EmailMessages;
use App\Services\Messages\FlashMessages;
use App\Services\TextProcess\TextProcess;
use App\Services\Validation\ValidationCustom;
use App\Services\WordPress\CandidateAccountMgmt;
use App\Services\WordPress\WpStatusHandling;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Twig\Environment;

/**
 * Class to processing account while:
 * -registering
 * -login
 * -logout
 * -activate
 * -resetting password.
 */
class ProcessAccount
{
  /**
   * E-mail and password passed by user.
   * 
   * @var array
   */
  protected $userData;
  
  /**
   * Keys of $userData array.
   * 
   * @var array
   */
  protected $keys;
  
  /**
   * Service for performing actions on WordPress table in db.
   * 
   * @var CandidateAccountMgmt
   */
  protected $wpMgmt;
  
  /**
   * Service for email sending
   * 
   * @var EmailMessages
   */
  protected $emailer;
  
  /**
   * Just Entity Manager.
   * 
   * @var EntityManagerInterface
   */
  protected $entityManager;
  
  /**
   * Twig environment to render template.
   * 
   * @var Environment
   */
  protected $twig;
  
  /**
   * Bag from $_SESSION with flash messages
   * 
   * @var FlashBagInterface
   */
  protected $flashBag;
  
  /**
   * Service for creating flash messages defined in 'config/services.yaml'
   * 
   * @var FlashMessages
   */
  protected $flashSrvMsg;
  
  /**
   * Form factory
   * 
   * @var FormFactoryInterface
   */
  protected $formFactory;
  
  /**
   * Logger.
   * 
   * @var LoggerInterface
   */
  protected $logger;
  
  /**
   * Perform actions on `intersynergy`.`user` table.
   * 
   * @var InfluenceUserTable
   */
  protected $userTable;
  
  /**
   * Parameter Bag with params from `config/services.yaml`.
   * 
   * @var ParameterBagInterface
   */
  protected $messages;
  
  /**
   * Processing DateTime objects:
   * -counting interval between timestamps
   * 
   * @var ProcessDateTime
   */
  protected $dateTime;
  
  /**
   * Text processing:
   * -name converting for `intersynergy`.`wp_posts` table
   * 
   * @var TextProcess
   */
  protected $text;
  
  /**
   * URL Generator.
   * 
   * @var UrlGeneratorInterface
   */
  protected $router; 
  
  /**
   * Password encoder selected by Symfony (configured in `security.yaml`)
   * 
   * @var UserPasswordEncoderInterface
   */
  protected $encoder;
  
  /**
   * Performing actions in WordPress and returning results's messages
   * 
   * @var WpStatusHandling
   */
  protected $wpStatus;
  
  /**
   * @param CandidateAccountMgmt         $wpMgmt
   * @param EmailMessages                $emailer
   * @param EntityManagerInterface       $entityManager  
   * @param Environment                  $twig           
   * @param FlashBagInterface            $flashBag       
   * @param FlashMessages                $flashSrvMsg
   * @param FormFactoryInterface         $formFactory
   * @param LoggerInterface              $logger         
   * @param InfluenceUserTable           $userTable   
   * @param ParameterBagInterface        $messages 
   * @param ProcessDateTime              $dateTime
   * @param TextProcess                  $text
   * @param UrlGeneratorInterface        $router           
   * @param UserPasswordEncoderInterface $encoder
   * @param WpStatusHandling             $wpStatus        
   */
  public function __construct(
      CandidateAccountMgmt $wpMgmt,
      EmailMessages $emailer,
      EntityManagerInterface $entityManager,
      Environment $twig,
      FlashBagInterface $flashBag,
      FlashMessages $flashSrvMsg,
      FormFactoryInterface $formFactory,
      LoggerInterface $logger,
      InfluenceUserTable $userTable,
      ParameterBagInterface $messages,
      ProcessDateTime $dateTime,
      TextProcess $text,
      UrlGeneratorInterface  $router,
      UserPasswordEncoderInterface $encoder,
      WpStatusHandling $wpStatus   
  ) {
    $this->wpMgmt = $wpMgmt;
    $this->emailer = $emailer;
    $this->entityManager = $entityManager;
    $this->twig = $twig;
    $this->flashBag = $flashBag;
    $this->flashSrvMsg = $flashSrvMsg;
    $this->formFactory = $formFactory;
    $this->logger = $logger;
    $this->userTable = $userTable;   
    $this->messages = $messages;
    $this->dateTime = $dateTime;
    $this->text = $text;
    $this->router = $router;
    $this->encoder = $encoder;
    $this->wpStatus = $wpStatus;
  }
  
  /**
   * Set userData and keys.
   * 
   * @param array $userData
   */
  protected function setData(array $userData): void
  {
    $this->userData = $userData;
    $this->keys = array_keys($this->userData);
  }

  /**
   * Validate email and password provided by user.
   *
   * @param  string $email
   * @param  string $password
   * @param  bool   $silence  If equals 'true', validation message will be hidden
   * 
   * @return bool   $result   Return false if validation failed, true otherwise
   */
  protected function checkProvidedData(string $email, string $pwd, bool $silence = false): bool
  {
    $validator = new ValidationCustom();
    $validator->validateEmail($email);
    $validator->validatePassword($pwd);
    $message = $validator->returnMessage();
    if ('' !== $message && $silence) {
      $this->flashSrvMsg->flashServicesMessage('app.login.email.pwd.fail');
      $result = false;
    } else if ('' !== $message && !$silence) {
      $this->flashBag->add('error', $message);
      $result = false;
    } else if ('' === $message) {
      $result = true;
    }
    
    return $result;
  }

  /**
   * Check that user is enabled.
   * 
   * @param User $user An user which enable status will be checked
   * 
   * @return bool Return true if user is enabled, otherwise false
   */
  protected function checkEnabled(User $user): bool
  {
    if (!$user->getEnabled()) {
      $this->flashSrvMsg->flashServicesMessage('app.login.acc.disabled');
      $status = false;
    }
    
    return $status ?? true;
  }

  /**
   * Check that user passed valid password.
   * If not - create message with error.
   * 
   * @param User $user An user whose password will be checked to compare with
   *                   provided by form
   * @return bool      False if checking fails, true otherwise
   */
  protected function encodedPwdValidation(User $user): bool
  {
    if (!$this->encoder->isPasswordValid(
        $user,
        $this->userData[$this->keys[1]]
    )) {
      $this->flashSrvMsg->flashServicesMessage('app.login.pwd.fail');
      $result = false;
    }
    
    return $result ?? true;
  }
}
/*............................................................................*/