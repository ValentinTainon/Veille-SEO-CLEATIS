<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ArticleCrudController extends AbstractCrudController
{
    public function __construct() {}

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Gestion des articles')
                    ->setEntityLabelInPlural('articles')
                    ->setEntityLabelInSingular('article')
                    ->setDefaultSort(['datePublication' => 'DESC'])
                    ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(EntityFilter::new('user'))
                        ->add(EntityFilter::new('fluxRss'));
    }

    public function configureActions(Actions $actions): Actions
    {
        if ($this->isGranted('ROLE_REDACTEUR')){
            return $actions->setPermissions([Action::INDEX, Action::NEW, Action::EDIT, Action::DELETE])
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Créer un article'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, fn (Action $action) => $action->setLabel('Créer et ajouter un nouvel article'));
        }
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_EDIT)
            yield DateTimeField::new('datePublication')->setDisabled();
        else
            yield DateTimeField::new('datePublication')->hideWhenCreating();

        yield TextField::new('nomSource');
        yield TextField::new('lienSource');
        yield TextField::new('titre');
        yield TextareaField::new('description')->setFormType(CKEditorType::class);
        yield TextField::new('lienArticle')->setLabel('Lien Article Complet');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Article) {
            $entityInstance->setUser($this->getUser())
                ->setDatePublication(new \DateTime('now'));
        }
        parent::persistEntity($entityManager, $entityInstance);
    }
}
