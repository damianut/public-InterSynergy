<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Database;

use App\Services\Messages\EmailMessages;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class InfluenceDB
{
  /**
   * Service for email sending
   * 
   * @var EmailMessages
   */
  private $emailer;
  
  /**
   * Entity Manager from Doctrine.
   * 
   * @var EntityManagerInterface
   */
  private $em;
  
  /**
   * Service for creating logs.
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
   * @param EmailMessages          $emailer
   * @param EntityManagerInterface $em
   * @param LoggerInterface        $logger
   * @param ParameterBagInterface  $messages
   */
  public function __construct(
      EmailMessages $emailer,
      EntityManagerInterface $em,
      LoggerInterface $logger,
      ParameterBagInterface $messages
  )
  {
    $this->emailer = $emailer;
    $this->em = $em;
    $this->logger = $logger;
    $this->messages = $messages;
  }
  
  /**
   * Try execute statement on `intersynergy` database
   * 
   * @param  string $sql    Statement to execute
   * 
   * @return array          Status of statement executing and email sending
   */
  public function tryStatement(string $sql): array
  {
    $connection = $this->em->getConnection();
    $statement = $connection->prepare($sql);
    try {
      $statement->execute();
      $status = true;
      //Fictitious email sending status.
      $emailStatus = true;
    } catch (DBALException $e) {
      $eMsg = $e->__toString();
      $this->logger->error($eMsg);
      $emailStatus = $this->emailer->sendEmail(
          $this->messages->get('app.admin.email'),
          $this->messages->get('app.db.exec.failure'),
          $eMsg
      );
      $emailStatus = $emailStatus == 0 ? false : true;
      $status = false;
    }
    
    return [$status, $emailStatus];
  }
}

/*............................................................................*/