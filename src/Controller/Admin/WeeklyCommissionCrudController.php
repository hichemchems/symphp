<?php

namespace App\Controller\Admin;

use App\Entity\WeeklyCommission;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class WeeklyCommissionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return WeeklyCommission::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commission Hebdomadaire')
            ->setEntityLabelInPlural('Commissions Hebdomadaires')
            ->setPageTitle('index', 'Gestion des Commissions Hebdomadaires')
            ->setPageTitle('edit', 'Modifier Commission')
            ->setPageTitle('new', 'Nouvelle Commission')
            ->setPageTitle('detail', 'Détails de la Commission')
            ->setDefaultSort(['weekEnd' => 'DESC', 'employee' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('employee', 'Employé')
                ->setRequired(true)
                ->formatValue(function ($value, $entity) {
                    if ($entity && $entity->getEmployee()) {
                        return $entity->getEmployee()->getFirstName() . ' ' . $entity->getEmployee()->getLastName();
                    }
                    return '';
                }),

            DateTimeField::new('weekStart', 'Début de Semaine')
                ->setRequired(true)
                ->setFormat('dd/MM/yyyy'),

            DateTimeField::new('weekEnd', 'Fin de Semaine')
                ->setRequired(true)
                ->setFormat('dd/MM/yyyy'),

            NumberField::new('totalRevenueHt', 'Revenus HT')
                ->setRequired(true)
                ->setNumDecimals(2)
                ->setThousandsSeparator(' ')
                ->setCurrency('EUR'),

            NumberField::new('totalCommission', 'Commission Totale')
                ->setRequired(true)
                ->setNumDecimals(2)
                ->setThousandsSeparator(' ')
                ->setCurrency('EUR'),

            NumberField::new('clientsCount', 'Nombre de Clients')
                ->setRequired(true),

            BooleanField::new('validated', 'Validée')
                ->setRequired(true),

            BooleanField::new('paid', 'Payée')
                ->setRequired(true),

            DateTimeField::new('validatedAt', 'Date de Validation')
                ->hideOnForm()
                ->setFormat('dd/MM/yyyy HH:mm'),

            DateTimeField::new('paidAt', 'Date de Paiement')
                ->hideOnForm()
                ->setFormat('dd/MM/yyyy HH:mm'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_ADD_ANOTHER)
            ->add(Crud::PAGE_INDEX, Action::new('validate', 'Valider')
                ->setIcon('fas fa-check')
                ->setCssClass('btn btn-success')
                ->displayIf(function ($entity) {
                    return !$entity->isValidated();
                })
                ->linkToCrudAction('validateCommission')
            )
            ->add(Crud::PAGE_INDEX, Action::new('markPaid', 'Marquer Payée')
                ->setIcon('fas fa-money-bill-wave')
                ->setCssClass('btn btn-primary')
                ->displayIf(function ($entity) {
                    return $entity->isValidated() && !$entity->isPaid();
                })
                ->linkToCrudAction('markCommissionPaid')
            );
    }

    public function validateCommission()
    {
        $commission = $this->getContext()->getEntity()->getInstance();

        if (!$commission->isValidated()) {
            $commission->setValidated(true);
            $commission->setValidatedAt(new \DateTime());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Commission validée avec succès.');
        }

        return $this->redirect($this->getContext()->getReferrer());
    }

    public function markCommissionPaid()
    {
        $commission = $this->getContext()->getEntity()->getInstance();

        if ($commission->isValidated() && !$commission->isPaid()) {
            $commission->setPaid(true);
            $commission->setPaidAt(new \DateTime());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Commission marquée comme payée.');
        }

        return $this->redirect($this->getContext()->getReferrer());
    }
}
