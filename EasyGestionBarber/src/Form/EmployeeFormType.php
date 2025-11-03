<?php

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;

class EmployeeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire']),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire']),
                ],
            ])
            ->add('emailConfirm', EmailType::class, [
                'label' => 'Confirmer l\'email',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez confirmer l\'email']),
                    new Callback([
                        'callback' => function($value, $context) {
                            $originalEmail = $context->getRoot()->get('email')->getData();
                            if ($value !== $originalEmail) {
                                $context->buildViolation('Les emails ne correspondent pas')
                                    ->addViolation();
                            }
                        }
                    ])
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('plainPasswordConfirm', PasswordType::class, [
                'label' => 'Confirmer le mot de passe',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez confirmer le mot de passe']),
                    new Callback([
                        'callback' => function($value, $context) {
                            $originalPassword = $context->getRoot()->get('plainPassword')->getData();
                            if ($value !== $originalPassword) {
                                $context->buildViolation('Les mots de passe ne correspondent pas')
                                    ->addViolation();
                            }
                        }
                    ])
                ],
            ])
            ->add('commissionPercentage', NumberType::class, [
                'label' => 'Pourcentage de commission (%)',
                'constraints' => [
                    new NotBlank(['message' => 'Le pourcentage de commission est obligatoire']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }
}
