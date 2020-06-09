<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */
declare(strict_types=1);

namespace App\Services\WordPress;

use App\Services\Database\InfluenceDB;
use App\Services\DateTime\ProcessDateTime;
use App\Services\TextProcess\TextProcess;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class for creating, editing or deleting candidate by perform actions on
 * `intersynergy`.`wp_posts` and `intersynergy`.`wp_postmeta` tables.
 */
class CandidateAccountMgmt
{
  /**
   * Performing actions on `intersynergy` database
   * 
   * @var InfluenceDB
   */
  private $influenceDB;
  
  /**
   * Parameter Bag with params from `config/services.yaml`.
   * 
   * @var ParameterBagInterface
   */
  private $messages;
  
  /**
   * Performing actions on DateTime objects
   * 
   * @var ProcessDateTime
   */
  private $dateTime;
    
  /**
   * Converting names and surnames
   * 
   * @var TextProcess
   */
  private $text;
  
  /**
   * @param TextProcess $text
   * @param ParameterBagInterface $messages
   * @param ProcessDateTime $dateTime
   * @param InfluenceDB $influenceDB
   */
  public function __construct(
      TextProcess $text,
      ParameterBagInterface $messages,
      ProcessDateTime $dateTime,
      InfluenceDB $influenceDB
  )
  {
    $this->text = $text;
    $this->messages = $messages;
    $this->dateTime = $dateTime;
    $this->influenceDB = $influenceDB;
  }
    
  /**
   * Create row in `intersynergy`.`wp_posts` table.
   * This row will be contains data of instance of Candidate CPT from WordPress.
   * 
   * @param  int    $user_id_value  ID of candidate from `intersynergy`.`user`
   * @param  string $candidateEmail Email of candidate
   * 
   * @return array                  First field of array indicate status of inserting,
   *                                second field indiciate optionally e-mail sending.
   */
  public function createCandidate(int $user_id_value, string $candidateEmail): array
  {
    $currentDateTime = $this->dateTime->wpDateTime();
    $guidValue = $this->messages->get('app.create.guid');
    $sql = "BEGIN; INSERT INTO `wp_posts` (".
        "`ID`, ".
        "`post_author`, ".
        "`post_date`, ".
        "`post_date_gmt`, ".
        "`post_content`, ".
        "`post_title`, ".
        "`post_excerpt`, ".
        "`post_status`, ".
        "`comment_status`, ".
        "`ping_status`, ".
        "`post_password`, ".
        "`post_name`, ".
        "`to_ping`, ".
        "`pinged`, ".
        "`post_modified`, ".
        "`post_modified_gmt`, ".
        "`post_content_filtered`, ".
        "`post_parent`, ".
        "`guid`, ".
        "`menu_order`, ".
        "`post_type`, ".
        "`post_mime_type`, ".
        "`comment_count`".
        ") VALUES (".
            "NULL, ".
            "'1', ".
            "'".$currentDateTime."', ".
            "'".$currentDateTime."', ".
            "'".$candidateEmail."', ".
            "CONCAT('Anonymous Candidate', get_new_id()), ".
            "'', ".
            "'publish', ".
            "'closed', ".
            "'closed', ".
            "'', ".
            "CONCAT('anonymous-candidate', get_new_id()), ".
            "'', ".
            "'', ".
            "'".$currentDateTime."', ".
            "'".$currentDateTime."', ".
            "'', ".
            "'0', ".
            $guidValue.", ".
            "'0', ".
            "'candidate', ".
            "'', ".
            "'0'); ".
    "INSERT INTO `wp_postmeta` (".
        "`meta_id`, ".
        "`post_id`, ".
        "`meta_key`, ".
        "`meta_value`".
        ") VALUES (".
            "NULL, ".
            "get_current_post_id(), ".
            "'user_id', ".
            "'".$user_id_value."'); COMMIT;"
    ;
    
    return $this->influenceDB->tryStatement($sql);
  }

  /**
   * Update row in `intersynergy`.`wp_posts` table.
   * This row contains data of instance of Candidate CPT from WordPress.
   * 
   * @param  string $nameAndSurname Name and surname of candidate
   * @param  int    $user_id        ID of user from `intersynergy`.`user`
   * 
   * @return array                  First field of array indicate status of inserting,
   *                                second field indiciate optionally e-mail sending.
   */
  public function updateCandidate(string $nameAndSurname, int $user_id): array
  {
    $currentDateTime = $this->dateTime->wpDateTime();
    $post_name = $this->text->convertName($nameAndSurname);
    $sql = "UPDATE `wp_posts` as `t1` INNER JOIN `wp_postmeta` as `t2` SET ".
        "`t1`.`post_title` = '".$nameAndSurname."', ".
        "`t1`.`post_name` = CONCAT('".$post_name."', `t1`.`ID`), ".
        "`t1`.`post_modified` = '".$currentDateTime."', ".
        "`t1`.`post_modified_gmt` = '".$currentDateTime."' ".
        "WHERE `t2`.`meta_value` = '".$user_id."' && ".
        "`t2`.`post_id` = `t1`.`ID`;"
    ;
    
    return $this->influenceDB->tryStatement($sql);
  }
  
  /**
   * Delete row in `intersynergy`.`wp_posts` table.
   * This row contains data of instance of Candidate CPT from WordPress.
   * 
   * @param  int   $user_id ID of user from `intersynergy`.`user`
   * 
   * @return array          First field of array indicate status of inserting,
   *                        second field indiciate optionally e-mail sending.
   */
  public function deleteCandidate(int $user_id): array
  {
    $sql = "BEGIN; DELETE `t1`, `t2` FROM ".
        "`wp_posts` AS `t1` INNER JOIN `wp_postmeta` AS `t2` ".
        "WHERE `t2`.`meta_value` = '".$user_id."' ".
        "AND `t1`.`ID` = `t2`.`post_id`; ".
        "CALL truncate_wp_posts; COMMIT;"
    ;

    return $this->influenceDB->tryStatement($sql);
  }
}
/*............................................................................*/