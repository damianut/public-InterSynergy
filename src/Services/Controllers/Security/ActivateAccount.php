<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\Security;

use App\Entity\User;
use App\Services\EntityActions\EnableUser;
use App\Services\Validation\ValidationCustom;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validation;

/**
 * Activate account and return message about performed actions
 */
class ActivateAccount
{
  /**
   * Service of enabling User.
   * 
   * @var EnableUser
   */
  private $enabler;
  
  /**
   * Doctrine Entity Manager.
   * 
   * @var EntityManagerInterface
   */
  private $entityManager;
  
  /**
   * Bag with Parameters from `services.yaml`.
   * 
   * @var ParameterBagInterface
   */
  private $params;
  
  /**
   * Message about made Actions.
   * 
   * @var string
   */
  private $message;
  
  /**
   * Confirmation Token.
   * 
   * @var string
   */
  private $token;
  
  /**
   * Service for custom Validation.
   * 
   * @var ValidationCustom
   */
  private $validator;

  /**
   * @param EnableUser             $enabler
   * @param EntityManagerInterface $entityManager
   * @param ParameterBagInterface  $params
   * @param ValidationCustom       $validator
   */
  public function __construct(
      EnableUser $enabler,  
      EntityManagerInterface $entityManager,
      ParameterBagInterface $params,
      ValidationCustom $validator
  ) {
    $this->enabler = $enabler;
    $this->entityManager = $entityManager;
    $this->params = $params;
    $this->message = '';
    $this->validator = $validator;
  }
  
  /**
   * Activating account.
   * 
   * @param string $token Confirmation token
   * 
   * @return string       Message about activating
   */
  public function activateAccount($token): string
  {
    $this->token = $token;
    //Validate token.
    $this->validator->validateUuid($this->token);
    $this->message .= $this->validator->returnMessage();
    
    /**
     * If token is valid - continue.
     */
    if ('' === $this->message) {
      $repository = $this->entityManager->getRepository(User::class);
      $user = $repository->findOneBy([
          'blockedConfirmationToken' => $this->token,
      ]);
  
      // If user with given token exists - activate him.
      // Else not.
      // In both cases prepare message to user about activating or not.
      if ($user instanceof User) {
        // If activating procedure fails - prepare message about it.
        if ($this->enabler->enable($user)) {
          $this->message .= $this->params->get('app.activate');
        } else {
          $this->message .= $this->params->get('app.activate.fail');
        }
      } else {
        $this->message .=
            $this->params->get('app.activate.token.404');
      }
    }
    
    return $this->message;
  }
}
/*............................................................................*/
