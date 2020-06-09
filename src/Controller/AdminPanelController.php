<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Controller;

use App\Services\Controllers\AdminPanel\AdminPanelLogic;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for Admin's Panel.
 * Admin's panel serves to display users's data,create, edit and delete users.
 */
class AdminPanelController extends AbstractController
{ 
  /**
   * Class with logic for all routes of AdminPanelController
   * 
   * @var AdminPanelLogic
   */
  private $adminPanelLogic;
  
  /**
   * @param AdminPanelLogic $adminPanelLogic 
   */
  public function __construct(AdminPanelLogic $adminPanelLogic)
  {
    $this->adminPanelLogic = $adminPanelLogic;
  }
  
  /**
   * Admin's Panel Main Page.
   * 
   * @Route("/admin-panel", name="admin-panel")
   */
  public function adminPanel()
  {
    return $this->adminPanelLogic->adminPanelResponse();
  }
  
  /**
   * @Route("/create-user", name="create-user")
   */
  public function createUser()
  {
    return $this->adminPanelLogic->createUserResponse();
  }
  
  /**
   * @Route("/edit-user", name="edit-user")
   */
  public function editUser()
  {
    return $this->adminPanelLogic->editUserResponse();
  }
  
  /**
   * @Route("/delete-user", name="delete-user")
   */
  public function deleteUser()
  {      
    return $this->adminPanelLogic->deleteUserResponse();
  }
}
/*............................................................................*/
