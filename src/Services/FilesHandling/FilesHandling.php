<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\FilesHandling;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Upload or delete PDF in 'public/upload/pdf' directory
 */
class FilesHandling
{
  /**
   * Utilities for managing file system.
   * 
   * @var Filesystem
   */
  private $filesystem;
  
  /**
   * Bag from $_SESSION with flash messages
   * 
   * @var FlashBagInterface
   */
  private $flashBag;
  
  /**
   * Logger.
   * 
   * @var LoggerInterface
   */
  private $logger;
  
  /**
   * Parameter Bag with params from `config/services.yaml`.
   * 
   * @var ParameterBagInterface
   */
  private $messages;
  
  /**
   * @param Filesystem            $filesystem
   * @param FlashBagInterface     $flashBag
   * @param LoggerInterface       $logger
   * @param ParameterBagInterface $messages
   */
  public function __construct(
      Filesystem $filesystem,
      FlashBagInterface $flashBag,
      LoggerInterface $logger,
      ParameterBagInterface $messages
  )
  {
    $this->filesystem = $filesystem;
    $this->flashBag = $flashBag;
    $this->logger = $logger;
    $this->messages = $messages;
  }
  
  /**
   * Try to Save PDF File.
   * 
   * @param  UploadedFile $file   PDF file to save
   * @param  User         $user   Owner of file
   * 
   * @return bool         $status True if saving succeeded, false otherwise
   */
  public function savePDFFile(UploadedFile $file, User $user): bool
  {
    $originalFilename = pathinfo(
        $file->getClientOriginalName(),
        PATHINFO_FILENAME
    );
    $safeFilename = preg_replace("/[^A-Za-z0-9_]/", '', $originalFilename);
    $newFilename =
        $safeFilename.
        '-'.
        uniqid().
        '.'.
        $file->guessExtension(); 
    try {
        $file->move(
            $this->messages->get('app.pdf.dir'),
            $newFilename
        );
        $user->setCVFilename($newFilename);
    } catch (FileException $e) {      
      $this->logger->error($e->__toString());
      $message = $this->messages->get('app.upload.pdf.fail');
      $this->flashBag->add('pdfError', $message);
      $status = false;
    }
    
    return $status ?? true;
  }
  
  /**
   * Remove PDF File
   * 
   * @param  User         $user   Owner of file
   * 
   * @return bool         $status True if saving succeeded, false otherwise
   */
  public function removePDFFile(User $user): bool
  {
    $fileFullDir =
        $this->messages->get('app.pdf.dir'). 
        '/'. 
        $user->getCVFilename();
    try {
      $this->filesystem->remove([$fileFullDir]);
      $flash = $this->messages->get('app.remove.pdf');
      $this->flashBag->add('notice', $flash);
      $status = true;
    } catch (IOException $e) {
      $message = $this->messages->get('app.remove.pdf.fail');
      $this->flashBag->add('error', $message);
      $this->logger->error($e->__toString());
      $status = false;
    }
    if ($status) {
      $user->setCVFilename(NULL);
    }
    
    return $status; 
  } 
}
/*............................................................................*/