<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */

namespace App\Services\EntityActions;

use App\Entity\User;

/**
 *  Class for enabling User entity by FOSUserBundle and Doctrine
 */
class EnableUser
{
  /**
   * Instance of ChangeFailedLogin class.
   * 
   * @var ChangeFailedLogin
   */
  private $changer;

  /**
   * @param ChangeFailedLogin
   */
  public function __construct(ChangeFailedLogin $changer)
  {
    $this->changer = $changer;
  }

  /**
   * Enabling user
   * 
   * @param User $user An user which will be enabled
   * 
   * @return bool      Result of enabling user
   */
  public function enable(User $user): bool
  {
    $user->setBlockedConfirmationToken(null);
    $user->setEnabled(true);
    
    return $this->changer->change($user, 0);
  }
}
/*............................................................................*/
