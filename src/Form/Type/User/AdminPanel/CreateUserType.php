<?php

/**
 * This file is part of InterSynergy Project
 *
 * (c) Damian Orzeszek <damianas1999@gmail.com>
 */

namespace App\Form\Type\User\AdminPanel;

use App\Entity\User;
use App\Form\Type\DateTime\DateCustomType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Constraints;

/**
 * Class to creating form for 'create-user' page
 */
class CreateUserType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
        ->add('email', Type\EmailType::class, [
            'label' => 'Email',
        ])
        ->add('roles', Type\ChoiceType::class, [
            'choices' => [
                'ROLE_USER' => 'user',
                'ROLE_ADMIN' => 'admin',
            ],
            'label' => 'Roles',
            'multiple' => true,
        ])
        ->add('password', Type\TextType::class, [
            'label' => 'Password',
        ])
        ->add('name', Type\TextType::class, [
            'label' => 'Imię',
            'required' => false,
        ])
        ->add('surname', Type\TextType::class, [
            'label' => 'Nazwisko',
            'required' => false,
        ])
        ->add('PESEL', Type\TextType::class, [
            'label' => 'PESEL',
            'required' => false,
        ])
        ->add('NIP', Type\TextType::class, [
            'label' => 'NIP',
            'required' => false,
        ])
        ->add('address', Type\TextType::class, [
            'label' => 'Adres zamieszkania',
            'required' => false,
        ])
        ->add('personDescription', Type\TextareaType::class, [
            'label' => 'Opis osoby',
            'required' => false,
        ])
        ->add('interests', Type\TextareaType::class, [
            'label' => 'Zainteresowania',
            'required' => false,
        ])
        ->add('skills', Type\TextareaType::class, [
            'label' => 'Umiejętności',
            'required' => false,
        ])
        ->add('experience', Type\TextareaType::class, [
            'label' => 'Doświadczenie',
            'required' => false,
        ])
        ->add('birthDate', DateCustomType::class, [
            'label' => 'Data urodzenia',
            'required' => false,
        ])
        ->add('CVFilename', Type\FileType::class, [
            'label' => 'CV',
            'mapped' => false,
            'required' => false,
            'constraints' => new Constraints\File([
                'maxSize' => '10240k',
                'maxSizeMessage' => 'Maksymalny rozmiar pliku to 10 MB.',
                'mimeTypes' => [
                    'application/pdf',
                    'application/x-pdf',
                ],
                'mimeTypesMessage' => 'Proszę wysłać plik formatu PDF',
            ]),
        ])
        ->add('rating', Type\IntegerType::class, [
            'label' => 'Rating (1-10)',
            'required' => false,
        ])
        ->add('Zapisz', Type\SubmitType::class)
    ;
  }
  
  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
        'data_class' => User::class,
    ]);
  }
}
/*............................................................................*/