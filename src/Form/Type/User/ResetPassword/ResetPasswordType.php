<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */

namespace App\Form\Type\User\ResetPassword;

use App\QuasiEntity\Resetter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Constraints;

/**
 * Class to creating form for 'use-resetter-token' page
 */
class ResetPasswordType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
        ->add('newPassword', Type\PasswordType::class, [
            'label' => 'Podaj hasło'
        ])
        ->add('repeatPassword', Type\PasswordType::class, [
            'label' => 'Powtórz hasło'
        ])
        ->add('submitPassword', Type\SubmitType::class, [
            'label' => 'Zmień hasło'
        ])
    ;
  }
  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
        'data_class' => Resetter::class,
    ]);
  }   
}
/*............................................................................*/