<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */

namespace App\Services\EntityActions;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception;
use Doctrine\ORM\OptimisticLockException;

/**
 * Class for updating User entity.
 *
 * FOSUserBundle update entity, then
 * Doctrine saves changes.
 */
class UpdateUser
{
  /**
   * Doctrine Entity Manager.
   * 
   * @var EntityManagerInterface
   */
  private $em;
  
  /**
   * @param EntityManagerInterface
   */
  public function __construct(EntityManagerInterface $em)
  {
    $this->em = $em;
  }
  
  /**
   * Update User entity and return boolean value about update was successful.
   * 
   * @param User $user User to update
   * 
   * @return bool      Status of updating.
   */
  public function update(User $user): bool
  {
    try {
      $this->em->persist($user);
      $this->em->flush();
    } catch (Exception | OptimisticLockException $e) {
      $status = false;
    }
    
    return $status ?? true;
  }
}
/*............................................................................*/
