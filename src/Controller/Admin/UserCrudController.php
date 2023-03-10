<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // use the 'setPermission()' method to set the permission of actions
            // (the same permission is granted to the action on all pages)
            ->setPermission('User', 'ROLE_ADMIN')

            // // you can set permissions for built-in actions in the same way
            ->setPermission(Action::NEW, 'ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('username'),
            ArrayField::new('roles'),
            TextField::new('email'),
            TextField::new('password'),
        ];
    }
}
