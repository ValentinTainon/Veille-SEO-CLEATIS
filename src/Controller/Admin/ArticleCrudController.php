<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
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
        return $crud->setPageTitle(Crud::PAGE_INDEX, 'Gestion des articles')
                    ->setEntityLabelInPlural('articles')
                    ->setEntityLabelInSingular('article')
                    ->setDefaultSort(['publicationDate' => 'DESC'])
                    ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(EntityFilter::new('user'))
                        ->add(EntityFilter::new('rssFeed'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Créer un article'))
                    ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, fn (Action $action) => $action->setLabel('Créer et ajouter un nouvel article'));
    }

    public function configureFields(string $pageName): iterable
    {
        $article = $this->getContext()->getEntity()->getInstance();

        yield DateTimeField::new('publicationDate')->hideWhenCreating()->setDisabled();
        yield TextField::new('source', 'Auteur');
        yield ImageField::new('imageName', 'Logo entreprise')->setBasePath('/uploads')->onlyOnIndex();

        if ($pageName === Crud::PAGE_EDIT && !$article->getRssFeed()) {
            yield TextField::new('imageFile', 'Logo de l\'entreprise (jpg, png, webp) Taille max: 2 Mo')->setFormType(VichImageType::class)->onlyWhenUpdating();
        } else {
            yield TextField::new('imageName', 'Url du logo')->onlyWhenUpdating()->setRequired(true);
        }

        yield TextField::new('imageFile', 'Logo de l\'entreprise (jpg, png, webp) Taille max: 2 Mo')->setFormType(VichImageType::class)->onlyWhenCreating()->setRequired(true);
        yield TextField::new('imageAlt', 'Attribut "alt" du logo');
        yield TextField::new('sourceLink', 'Site web de l\'entreprise');
        yield TextField::new('title', 'Titre');
        yield TextareaField::new('description')->setLabel($pageName === Crud::PAGE_INDEX ? 'Description' : 'Description (Texte ou directement du contenu HTML)')->setFormType(CKEditorType::class);
        yield TextField::new('articleLink')->setLabel($pageName === Crud::PAGE_INDEX ? 'Lien de l\'article complet' : 'Lien de l\'article complet (Si nécessaire)');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Article) {
            $entityInstance->setUser($this->getUser())
                ->setPublicationDate(new \DateTime('now'));
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Article && !$entityInstance->getRssFeed()) {
            $entityInstance->setUser($this->getUser())
                ->setPublicationDate(new \DateTime('now'));
        }
        parent::updateEntity($entityManager, $entityInstance);
    }
}
