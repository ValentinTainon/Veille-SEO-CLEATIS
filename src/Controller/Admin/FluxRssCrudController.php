<?php

namespace App\Controller\Admin;

use App\Entity\FluxRss;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FluxRssCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FluxRss::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // use the 'setPermission()' method to set the permission of actions
            // (the same permission is granted to the action on all pages)
            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('lienRss'),
        ];
    }
}
