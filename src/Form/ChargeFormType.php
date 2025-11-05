<?php

namespace App\Form;

use App\Entity\Charge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ChargeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire']),
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant (€)',
                'constraints' => [
                    new NotBlank(['message' => 'Le montant est obligatoire']),
                    new Positive(['message' => 'Le montant doit être positif']),
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Charge::class,
        ]);
    }
}
