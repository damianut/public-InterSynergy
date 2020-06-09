<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\WordPress;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Performing three types of actions on WordPress tables and creating messages
 * about results.
 * 
 * Three types of actions:
 * -creating
 * -editing
 * -deleting
 * 
 * candidates profiles from `intersynergy`.`wp_posts` and 
 * `intersynergy`.`wp_postmeta` tables.
 */
class WpStatusHandling
{
  /**
   * Service for working on WordPress's tables.
   * 
   * @var CandidateAccountMgmt
   */
  private $wpMgmt;
  
  /**
   * Parameters from `config/services.yaml`.
   * 
   * @var ParameterBagInterface
   */
  private $messages;
  
  /**
   * @param CandidateAccountMgmt  $wpMgmt
   * @param ParameterBagInterface $messages
   */
  public function __construct(
      CandidateAccountMgmt $wpMgmt,
      ParameterBagInterface $messages
  )
  {
    $this->wpMgmt = $wpMgmt;
    $this->messages = $messages;
  }
  
  /**
   * Creating user profile in WordPress.
   * This method append messages about creating to
   * '$this-wpMgmt->createCandidate' method.
   * 
   * @param  int    $id    ID of candidate from `intersynergy`.`user`
   * @param  string $email Email of candidate
   * 
   * @return string $flash Message about result
   */
  public function createCandidateMessage(int $id, string $email): string
  {
    $statuses = $this->wpMgmt->createCandidate($id, $email);
    
    return $this->messageBody($statuses, 'create');
  }
  
  /**
   * Update WordPress profile of candidate.
   * This method append messages about updating to
   * '$this->wpMgmt->updateCandidate' method.
   * 
   * @param  int    $id ID of candidate (user)
   * 
   * @return string     Message about updating
   */
  public function updateCandidateMessage(string $nameAndSurname, int $id): string
  {
    $statuses = $this->wpMgmt->updateCandidate($nameAndSurname, $id);
    
    return $this->messageBody($statuses, 'update');
  }
   
  /**
   * Delete WordPress profile of candidate.
   * This method append messages about deletion to
   * '$this->wpMgmt->deleteCandidate' method.
   * 
   * @param  int    $id ID of candidate (user)
   * 
   * @return string     Message about deletion
   */
  public function deleteCandidateMessage(int $id): string
  {
    $statuses = $this->wpMgmt->deleteCandidate($id);
    
    return $this->messageBody($statuses, 'delete');
  }
  
  /**
   * Creating message about performed actions on WordPress's tables
   * 
   * @param  array  $statuses Status of statement executing and email sending
   * @param  string $type     Type of action performed on WordPress's tables
   * 
   * @return string $flash    Message
   */
  private function messageBody(array $statuses, string $type): string
  {
    if ($statuses[0]) {
      $name = 'app.wp.'.$type;
      $flash = $this->messages->get($name);   
    }
    if (!$statuses[0]) {
      $name = 'app.wp.'.$type.'.fail';
      $flash = $this->messages->get($name);  
    }
    if (!$statuses[0] && !$statuses[1]) {
      $name = 'app.wp.email.fail';
      $flash .= "\n".$this->messages->get($name);   
    }
    if (!$statuses[0] && $statuses[1]) {
      $name = 'app.wp.email';
      $flash .= "\n".$this->messages->get($name);
    }
    
    return $flash;
  }
}
/*............................................................................*/