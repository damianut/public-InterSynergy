<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Controller;

use App\Services\Controllers\Security\ProcessLoginAccount;
use App\Services\Controllers\Security\ProcessRegisterAccount;
use App\Services\Controllers\Security\ProcessResetPasswordAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Security Controller for:
 * -register account
 * -login account
 * -logout account
 * -activate account
 * -reset account's password
 * 
 * in another words: for processing account.
 */
class SecurityController extends AbstractController
{
  /**
   * Handling login request.
   * 
   * @var ProcessLoginAccount
   */
  private $loginService;
  
  /**
   * Handling register account request.
   * 
   * @var ProcessRegisterAccount
   */
  private $registerService;
  
  /**
   * Handling reset password request.
   * 
   * @var ProcessResetPasswordAccount
   */
  private $resetterService;
  
  /**
   * Current Request.
   * 
   * @var Request
   */
  private $request;

  /**
   * @param ProcessLoginAccount         $loginService
   * @param ProcessRegisterAccount      $registerService
   * @param ProcessResetPasswordAccount $resetterService
   * @param RequestStack                $requestStack
   */
  public function __construct(
      ProcessLoginAccount $loginService,
      ProcessRegisterAccount $registerService,
      ProcessResetPasswordAccount $resetterService,
      RequestStack $requestStack
  ) {
    $this->loginService = $loginService;
    $this->registerService = $registerService;
    $this->resetterService = $resetterService;
    $this->request = $requestStack->getCurrentRequest();
  }
  
  /**
   * If user is logged, redirect to 'login-account' (User panel).
   * 
   * Next validate and render messages. Optionally activate account.
   * 
   * @Route("/main-page", name="main-page")
   */
  public function mainPage()
  { 
    return $this->loginService->mainPageLogic($this->request);
  }
  
  /**
   * Check data from $request and if data passed test - create user account.
   * 
   * @Route("/register-account", name="register-account", methods="post")
   */
  public function registerAccount()
  {
    $this->registerService->registerAccountProcess($this->request);
    return $this->redirectToRoute('main-page');
  }
  
  /**
   * @Route("/login-account", name="login-account")
   */
  public function loginAccount()
  {
    return $this->loginService->loginControllerProcess($this->request);
  }
  
  /**
   * Displayed page after the user changes data.
   * 
   * @Route("/successful", name="successful")
   */
  public function successful()
  { 
    return $this->render('successful-page/index.html.twig');
  }
  
  /**
   * Handling Request about Reset Password.
   * 
   * If successful, User receives mail with link to `use-resetter-token` page
   * 
   * @Route("/resetter", name="resetter", methods="post")
   */
  public function resetter()
  {
    return $this->resetterService->handleResetRequest($this->request);
  }

  /**
   * Page rendered after the user clicks link from email, which received
   * after reset password request.
   * 
   * @Route("/use-resetter-token", name="use-resetter-token")
   */
  public function resetterToken()
  {
    return $this->resetterService->handlePasswordChanging($this->request);
  }

  /**
   * @Route("/logout", name="logout")
   */
  public function logout()
  {
    return $this->loginService->deleteSession();
  }
}
/*............................................................................*/
