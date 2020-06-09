<?php

/**
 * This file is part of InterSynergy Project.
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\Controllers\Rest;

use App\Entity\User;
use App\Services\Validation\ValidationCustom;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;

/**
 * Logic of RESTful API for 'rest' Route.
 */
class RestLogic
{
  /**
   * Service for rendering Twig Templates.
   * 
   * @var Environment
   */
  private $twig;
  
  /**
   * Entity Manager.
   * 
   * @var EntityManagerInterface
   */
  private $em;
  
  /**
   * @param Environment            $twig
   * @param EntityManagerInterface $em
   */
  public function __construct(Environment $twig, EntityManagerInterface $em)
  {
    $this->twig = $twig;
    $this->em = $em;
  }

  /**
   * JSON Response for Recipient.
   * 
   * @param Request   $request  Recipient request
   * 
   * @return Response $response
   */
  public function response(Request $request): Response
  { 
    $query = $request->query->all();
    do {
      if (!$this->validateQuery($query)) {
        break;   
      }
      if ('' === $query["user"]) {
        $user = $this->retrieveAllUsers();
        break;
      }
      if ($this->intOrEmail($query["user"])) {
        $user = $this->retrieveUser($query["user"], 'id');
        break;
      } else {
        $user = $this->retrieveUser($query["user"], 'email');
        break;
      }
    } while (false);
    if ($user ?? null) {
      $jsonEncoded = $this->arrayToJson($user);
      $response = new Response($jsonEncoded);
    } else {
      $renderedTemplate = $this->twig->render('rest/index.html.twig');
      $response = new Response($renderedTemplate);
    }
    $response->setPrivate();
    
    return $response;
  }
  
  /**
   * Check that $_GET["user"] value is ID or not.
   * 
   * @param  string $identifier Value of $_GET["user"]
   * 
   * @return bool   $result     True if it's ID, false otherwise 
   */
  private function intOrEmail(string $identifier): bool
  {
    $integer = intval($identifier);
    if (0 !== $integer && $identifier === ''.$integer) {
      $result = true;
    }
    
    return $result ?? false;
  }
  
  /**
   * Validation of data from $_GET["user"].
   * 
   * @param string $identifier Value of $_GET["user"]
   * 
   * @return bool  $result     True if value is valid, false otherwise
   */
  private function validateIdentifier(string $identifier): bool
  {
    do {
      if ($result = $this->intOrEmail($identifier)) {
        break;   
      }
      $validator = new ValidationCustom();
      $validator->validateEmail($identifier);
      if ('' === $validator->returnMessage()) {
        $result = true;
      }
    } while (false);
    
    return $result ?? false;
  }
  
  /**
   * Validate $_GET.
   * 
   * @param  array     $query $_GET
   * 
   * @return User|null $user  User if exists
   */
  private function validateQuery(array $query): ?bool
  {
    do {
      if (1 !== count($query)) {
        break;   
      }
      if (!array_key_exists("user", $query)) {
        break;   
      }
      $identifier = $query["user"];
      if ('' === $identifier) {
        $result = true;
        break;   
      }
      if ($this->validateIdentifier($identifier)) {
        $result = true;
        break;   
      }
    } while (false);
    
    return $result ?? false;
  }
  
  /**
   * Check that User with given ID or e-mail exist.
   * 
   * @param  string     $identifier $_GET["user"]
   * @param  string     $type       Find User by ID or email
   * 
   * @return array|null $user       The objects
   */
  private function retrieveUser(string $identifier, string $type): ?array
  { 
    do {
      if (!\in_array($type, ['id', 'email'])) {
        break;
      }
      $user = $this->em->getRepository(User::class)->findBy([
          $type => $identifier,
      ]);
    } while (false);
    
    return $user ?? null;
  }
  
  /**
   * Retrieve all Users.
   * 
   * @return array $users Array with Users's data
   */
  private function retrieveAllUsers(): array
  {
    return $this->em->getRepository(User::class)->findAll();
  }
  
  /**
   * Convert Array of Users to JSON.
   * 
   * @param  array  $users Array with User's data
   * 
   * @return string $json  JSON string
   */
  private function arrayToJson(array $users): string
  {
    $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer()];
    $encoders = [new JsonEncoder()];
    $serializer = new Serializer($normalizers, $encoders);
    return $serializer->serialize($users, 'json');
  }
}
/*............................................................................*/