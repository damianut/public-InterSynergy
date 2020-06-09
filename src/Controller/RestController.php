<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Controller;

use App\Services\Controllers\Rest\RestLogic;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for RESTful API
 */
class RestController extends AbstractController
{
  /**
   * Logic of RESTful API for 'rest' Route.
   * 
   * @var RestLogic
   */
  private $restLogic;
  
  /**
   * @param RestLogic $restLogic
   */
  public function __construct(RestLogic $restLogic)
  {
    $this->restLogic = $restLogic;
  }
  
  /**
   * @Route("/rest", name="rest")
   */
  public function rest(Request $request)
  {
    return $this->restLogic->response($request);
  }
}