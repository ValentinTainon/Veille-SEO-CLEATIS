<?php

namespace App\Controller\Admin;

use App\Entity\FluxRss;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class FluxRssCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FluxRss::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Gestion des flux RSS');
    }

    public function configureActions(Actions $actions): Actions
    {
        if ($this->isGranted('ROLE_ADMIN')){
            return $actions->setPermissions([Action::INDEX, Action::NEW, Action::EDIT, Action::DELETE])
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Créer un flux RSS'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, fn (Action $action) => $action->setLabel('Créer et ajouter un nouveau flux RSS'));
        }
    }

    public function configureFields(string $pageName): iterable
    {
        return [TextField::new('lienRss')];
    }
}
