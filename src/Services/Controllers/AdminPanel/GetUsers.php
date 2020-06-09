<?php

/**
 * This file is part of InterSynergy Project.
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\AdminPanel;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class GetUsers
{
  /**
   * Entity Manager
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
   * Get all Users from `intersynergy`.`user` Table.
   * 
   * @return array $usersData Array with data of all users
   */
  public function getAll(): array
  {
    $repo = $this->em->getRepository(User::class);
    $users = $repo->findBy([]);
    for ($i = 0; $i < count($users); $i++) {
      $userData = [
          'id' => $users[$i]->getId(),
          'email' => $users[$i]->getEmail(),
          'enabled' => $users[$i]->getEnabled(),
          'failedLogin' => $users[$i]->getFailedLogin(),
          'name' => $users[$i]->getName(),
          'surname' => $users[$i]->getSurname(),
      ];
      $usersData[$i] = $userData;
    }
    
    return $usersData;
  }
}
/*............................................................................*/