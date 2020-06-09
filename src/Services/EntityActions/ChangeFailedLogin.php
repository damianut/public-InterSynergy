<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */

namespace App\Services\EntityActions;

use App\Entity\User;

/**
 * A class for changing the value of the failed logins counter –
 * this counter is a User's property.
 */
class ChangeFailedLogin
{
  /**
   * Instance of UpdateUser class
   * 
   * @var UpdateUser
   */
  private $updater;

  /**
   * @param UpdateUser $updater
   */
  public function __construct(UpdateUser $updater)
  {
    $this->updater = $updater;
  }

  /**
   * Changing user data.
   * 
   * @param User $user  An user whose data will be changed
   * @param int  $value Number of failed login attempts
   * 
   * @return bool       Result of changing data
   */
  public function change(User $user, ?int $value = 0): bool
  {
    /**
     * Change previously mentioned counter.
     */
    $user->setFailedLogin($value);
    
    /**
     * Flush changes and return boolean value about update was successful
     */
    
    return $this->updater->update($user);
  }
}
/*............................................................................*/
