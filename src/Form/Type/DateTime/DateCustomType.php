<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */

namespace App\Form\Type\DateTime;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateCustomType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
        'years' => range((int) date('Y') - 100, (int) date('Y') - 15),
        'format' => 'dd-MM-yyyy',
    ]);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getParent()
  {
      return DateType::class;
  }
}