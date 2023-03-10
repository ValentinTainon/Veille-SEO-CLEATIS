<?php

namespace App\Controller\Admin;

use App\Entity\FluxRss;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FluxRssCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FluxRss::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('lienRss'),
        ];
    }
}
