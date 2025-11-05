<?php

namespace App\Controller\Admin;

use App\Entity\Package;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

class PackageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Package::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom du forfait'),
            NumberField::new('price', 'Prix TTC (€)')
                ->setNumDecimals(2)
                ->setHelp('Prix Toutes Taxes Comprises'),
            TextEditorField::new('description', 'Description')
                ->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Désactiver la suppression pour éviter les contraintes de clés étrangères
            ->disable(Action::DELETE)
            ->disable(Action::BATCH_DELETE)
            // Garder les autres actions (voir, éditer, créer)
            ->add(Action::INDEX, Action::DETAIL);
    }
}
