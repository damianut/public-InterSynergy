<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */

namespace App\Services\EntityActions;

use App\Entity\User;
use Ramsey\Uuid\Uuid;

/*
 * Class for banning User. Banning means: set property 'enabled' to false
 * and set blockedConfirmationToken which serves to authenticate user
 * while unblock's process.
 */
class UserBanning
{
  /**
   * @var UpdateUser
   */
  private $updater;

  /**
   * @param UpdateUser $updater Instance of UpdateUser class which serves
   *                            to flush changes made on $user
   */
  public function __construct(UpdateUser $updater) {
    $this->updater = $updater;
  }
  
  /**
   * Ban user.
   * 
   * @param User $user The user who will be banned
   */
  public function banningProcedure(User $user)
  {
    $user->setEnabled(false);
    $token = Uuid::uuid4();
    $user->setBlockedConfirmationToken($token);
    $this->updater->update($user);
  }
}
/*............................................................................*/
