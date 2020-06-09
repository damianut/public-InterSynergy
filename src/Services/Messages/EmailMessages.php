<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Messages;

use App\Services\Messages\FlashMessages;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use \Swift_Mailer;

/**
 * Sending emails by SwiftMailer.
 */
class EmailMessages
{
  /**
   * Service for creating flash messages defined in 'config/services.yaml'
   * 
   * @var FlashMessages
   */
  private $flashSrvMsg;
  
  /**
   * Service for creating logs
   * 
   * @var LoggerInterface
   */
  private $logger;
  
  /**
   * Parameter bag with defined messages from 'config/services.yaml'
   * 
   * @var ParameterBagInterface
   */
  private $messages;
  
  /**
   * SwiftMailer.
   * 
   * @var Swift_Mailer
   */
  private $mailer;
  
  /**
   * @param FlashMessages $flashSrvMsg
   * @param LoggerInterface $logger
   * @param ParameterBagInterface $messages
   * @param Swift_Mailer $mailer
   */
  public function __construct(
      FlashMessages $flashSrvMsg,
      LoggerInterface $logger,
      ParameterBagInterface $messages,
      Swift_Mailer $mailer
  )
  {
    $this->flashSrvMsg = $flashSrvMsg;
    $this->logger = $logger;
    $this->messages = $messages;
    $this->mailer = $mailer;
  }
    
  /**
   * Generally defined email sending by SwiftMailer
   * 
   * @param  string $email   Email of receiver
   * @param  string $title   Title of email
   * @param  string $content contains content without URL with confirmation token
   * 
   * @return bool   $status  Indicate, that sending email was successful or not
   */
  public function sendEmail(string $email, string $title, string $content): bool
  {
    $message = new \Swift_Message($title);
    $message->setFrom($this->messages->get('app.email.sender'));
    $message->setTo($email);
    $message->setBody($content);
    /**
     * Method 'send' of \Swift_Mailer return the number of successful recipient.
     * 0 mean, that email was not delivered to user.
     * In this case, this method return false to indicate failed delivering
     * or return true otherwise.
     * In case of failure, flash message and log will be created.
     */
    if (0 === $this->mailer->send($message)) {
      $this->flashSrvMsg->flashServicesMessage('app.mail.fail');
      $logMsg = $this->message->get('app.logger.mailer.fail');
      $this->logger->error($logMsg);
      $status = false;
    } else {
      $status = true;   
    }
    
    return $status;
  }
}
/*............................................................................*/