<?php

namespace App\Controller\Admin;

use App\Entity\Revenue;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class RevenueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Revenue::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            AssociationField::new('employee', 'Nom de l\'Employé')
                ->setTemplatePath('admin/fields/employee_name.html.twig'),
            NumberField::new('amountHt', 'Montant HT')
                ->setNumDecimals(2),
            NumberField::new('amountTtc', 'Montant TTC')
                ->setNumDecimals(2),
            DateTimeField::new('date', 'Date'),
        ];
    }
}
