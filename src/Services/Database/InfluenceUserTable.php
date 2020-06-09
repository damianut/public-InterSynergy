<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Database;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class to perform actions on `intersynergy`.`user` table
 */
class InfluenceUserTable
{
  /**
   * Entity Manager from Doctrine.
   * 
   * @var EntityManagerInterface
   */
  private $em;
  
  /**
   * @param EntityManagerInterface $em
   */
  public function __construct(EntityManagerInterface $em)
  {
    $this->em = $em;   
  }
  
  /**
   * Check that user exists in `intersynergy`.`user` table.
   * E-mail is unequivocal identifier of user.
   * 
   * @param string      $email   E-mail of user
   * 
   * @return User|null  $user    Instance of user with e-mail provided by form
   */
  public function userInDatabase(string $email): ?User 
  {
    $repo = $this->em->getRepository(User::class);
    return $repo->findOneBy([
        'email' => $email,
    ]);
  }
  
  /**
   * Retrieve User depends on ID
   * 
   * @param  int       $id   User's ID
   * 
   * @return User|null $user
   */
  public function retrieveUser(int $id): ?User
  {
    $repo = $this->em->getRepository(User::class);
    
    return $repo->findOneBy(['id' => $id]);
  }
}
/*............................................................................*/