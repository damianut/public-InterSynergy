<?php

namespace App\QuasiEntity;

use Symfony\Component\Validator\Constraints as Assert;

class Resetter
{
  /**
   * @param string $token Token from email sended after reset password's request
   */
  public function __construct(string $token)
  {
    $this->setToken($token);    
  }
  
  /**
   * @Assert\NotBlank
   */
  private $token;
  
  /**
   * @Assert\Length(min=8,minMessage="Hasło musi mieć minimum 8 znaków.")
   * @Assert\Regex(
   *     pattern = "/^\w+$/",
   *     message = "Hasło może składać się wyłącznie z cyfr, liter i znaku '_'"
   * )
   */
  private $newPassword;
  
  /**
   * @Assert\Length(min=8,minMessage="Hasło musi mieć minimum 8 znaków")
   * @Assert\Regex(
   *     pattern = "/^\w+$/",
   *     message = "Hasło może składać się wyłącznie z cyfr, liter i znaku '_'"
   * )
   */
  private $repeatPassword;
  
  public function getToken(): string
  {
    return (string) $this->token;   
  }
  
  public function setToken(string $token): self
  {
    $this->token = $token;
    
    return $this;   
  }
  
  public function getNewPassword(): ?string
  {
    return (string) $this->newPassword;   
  }
  
  public function setNewPassword(string $newPassword): self
  {
    $this->newPassword = $newPassword;
    
    return $this;
  }
  
  public function getRepeatPassword(): ?string
  {
    return (string) $this->repeatPassword;
  }
  
  public function setRepeatPassword(string $repeatPassword): self
  {
    $this->repeatPassword = $repeatPassword;
    
    return $this;   
  }
}