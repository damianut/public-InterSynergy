<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\DateTime;

/**
 * Processing DateTime objects:
 * -counting interval between timestamps
 * -formating DateTime to format needed in 'wp_posts'
 */
class ProcessDateTime
{
  /**
   * Counting number of seconds from now to time from DateTime object.
   * 
   * @param  \DateTime $dateTime1
   * 
   * @return int       $seconds   Time in seconds. 
   */
  public function intervalSeconds(\DateTime $dateTime1): int
  {
    $dateTime2 = new \DateTime();
    $seconds2 = $dateTime2->getTimestamp();
    $seconds1 = $dateTime1->getTimestamp();
    $seconds = $seconds2 - $seconds1;
    
    return $seconds;
  }
  
     
  /**
   * Get current date and time in "post_date" format 
   * from `intersynergy`.`wp_posts`.
   * 
   * @return string Formatted current date and time.
   */
  public function wpDateTime(): string
  {
    $type = 'Y-m-d H:i:s';
    $datetime = new \DateTime('now');
 
    return $datetime->format($type);
  }
}
/*............................................................................*/
