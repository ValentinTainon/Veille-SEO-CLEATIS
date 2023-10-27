<?php

namespace App\Controller\Admin;

use App\Entity\RssFeed;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class RssFeedCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RssFeed::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Flux RSS')
                    ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un Flux RSS')
                    ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le Flux RSS');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Ajouter un Flux RSS'))
                    ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, fn (Action $action) => $action->setLabel('Valider et ajouter un nouveau Flux RSS'))
                    ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn (Action $action) => $action->setLabel('Valider'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('xmlLink', 'Lien xml')->setMaxLength(50);
    }
}
