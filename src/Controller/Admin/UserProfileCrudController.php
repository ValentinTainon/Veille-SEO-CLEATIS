<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\Validator\Constraints\Regex;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Symfony\Component\Validator\Constraints\Length;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

class UserProfileCrudController extends AbstractCrudController
{
    public function __construct(private TotpAuthenticatorInterface $totpAuthenticatorInterface, private AuthorizationCheckerInterface $authorizationChecker, private EntityManagerInterface $entityManager, private UserPasswordHasherInterface $userPasswordHasher, private EmailVerifier $emailVerifier)
    {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle(Crud::PAGE_INDEX, 'Mon profil')
                    ->setPageTitle(Crud::PAGE_EDIT, 'Modifier mon profil');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->where('entity.id = :userId')->setParameter('userId', $this->getUser()->getId());
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('username', 'Nom d\'utilisateur');
        yield ArrayField::new('roles', 'Rôle')->setDisabled()->setRequired(false);
        yield TextField::new('email');
        yield TextField::new('password')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
            'type' => PasswordType::class,
            'first_options' => ['label' => 'Nouveau mot de passe', 'hash_property_path' => 'password'],
            'second_options' => ['label' => 'Répéter le nouveau mot de passe'],
            'mapped' => false,
            'constraints' => [
                new Length(['max' => 4096]), // max length allowed by Symfony for security reasons
                new Regex([
                    'pattern' => '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&.*-]).{8,}$/',
                    'message' => 'Votre mot de passe doit contenir au minimum 8 caractères avec au moins une lettre majuscule,
                    une lettre minuscule, un chiffre et un caractère spécial.'
                ]),
            ],
            ])
            ->onlyWhenUpdating()
            ->setRequired(false);
        yield BooleanField::new('isVerified', 'Utilisateur vérifié')->onlyOnIndex()->setDisabled();
        yield BooleanField::new('isEnable2fa', 'Double authentification')->setDisabled($pageName === Crud::PAGE_INDEX);
        yield TextField::new('checkPassword')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions([
                'label' => 'Afin de valider les modifications, veuillez saisir votre mot de passe actuel',
                'mapped' => false,
                'constraints' => [
                    new UserPassword([
                        'message' => 'Votre mot de passe actuel ne correspond pas',
                    ])
                ],
            ])
            ->onlyWhenUpdating()
            ->setRequired(true);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User){
            // Activer/Désactiver la double authentification
            if ($entityInstance->isEnable2fa() && !$entityInstance->isTotpAuthenticationEnabled()) {
                $totpSecret = $this->totpAuthenticatorInterface->generateSecret();
                $entityInstance->setTotpAuthenticationSecret($totpSecret);
                $this->addFlash('success', 'Double authentification activé pour ' . $entityInstance->getUsername() . ', veuillez télécharger une application d\'authentification et scanner votre QR Code situé dans le menu à gauche');
            } elseif (!$entityInstance->isEnable2fa() && $entityInstance->isTotpAuthenticationEnabled()) {
                $totpSecret = null;
                $entityInstance->setTotpAuthenticationSecret($totpSecret);
                $this->addFlash('success', 'Double authentification désactivé pour ' . $entityInstance->getUsername());
            }
        }
        parent::updateEntity($entityManager, $entityInstance);
    }
}
