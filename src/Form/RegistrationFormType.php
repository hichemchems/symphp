<?php

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', null, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ]
            ])
            ->add('lastName', null, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom'
                ]
            ])
            ->add('email', null, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre.email@exemple.com'
                ]
            ])
            ->add('emailConfirm', \Symfony\Component\Form\Extension\Core\Type\EmailType::class, [
                'label' => 'Confirmer l\'email',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Confirmer votre email'
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Veuillez confirmer votre email',
                    ]),
                    new \Symfony\Component\Validator\Constraints\Callback([
                        'callback' => function($value, $context) {
                            $originalEmail = $context->getRoot()->get('email')->getData();
                            if ($value !== $originalEmail) {
                                $context->buildViolation('Les emails ne correspondent pas')
                                    ->addViolation();
                            }
                        }
                    ])
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Minimum 6 caractères',
                    'autocomplete' => 'new-password'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('plainPasswordConfirm', PasswordType::class, [
                'label' => 'Confirmer le mot de passe',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Confirmer votre mot de passe'
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Veuillez confirmer votre mot de passe',
                    ]),
                    new \Symfony\Component\Validator\Constraints\Callback([
                        'callback' => function($value, $context) {
                            $originalPassword = $context->getRoot()->get('plainPassword')->getData();
                            if ($value !== $originalPassword) {
                                $context->buildViolation('Les mots de passe ne correspondent pas')
                                    ->addViolation();
                            }
                        }
                    ])
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation.',
                    ]),
                ],
            ])
        ;

        // Force admin role for registration
        $builder->getData()->setRoles(['ROLE_ADMIN']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }
}
