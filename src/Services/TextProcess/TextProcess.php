<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\TextProcess;

/**
 * Text processing:
 * -name converting for `intersynergy`.`wp_posts` table
 */
class TextProcess
{
  /**
   * Convert uppercase letter to lowercase and whitespace to hyphen.
   * 
   * @param  string $nameAndSurname Name and surname of candidate.
   * 
   * @return string $converted      String converted to format needed in `post_name` column in `intersynergy`.`wp_posts`
   */
  public function convertName(string $nameAndSurname): string
  {
    $converted = str_replace(' ', '-', $nameAndSurname);
    
    return strtolower($converted);   
  }
}