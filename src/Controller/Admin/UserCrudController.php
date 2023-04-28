<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Symfony\Component\Validator\Constraints\Length;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private TotpAuthenticatorInterface $totpAuthenticatorInterface, private UserRepository $userRepository, private AuthorizationCheckerInterface $authorizationChecker, private EntityManagerInterface $entityManager, private UserPasswordHasherInterface $userPasswordHasher, private EmailVerifier $emailVerifier)
    {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        if ($this->isGranted('ROLE_ADMIN'))
            $crud->setPageTitle('index', 'Gestion des utilisateurs');
        else if ($this->isGranted('ROLE_REDACTEUR'))
            $crud->setPageTitle('index', 'Gestion de votre profil')->showEntityActionsInlined();

        return $crud->setEntityLabelInPlural('utilisateurs')->setEntityLabelInSingular('utilisateur');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (!$this->isGranted('ROLE_ADMIN') && $this->isGranted('ROLE_REDACTEUR')) {
            $userId = $this->getUser()->getId();
            $queryBuilder->where('entity.id = :userId')->setParameter('userId', $userId)->setMaxResults(1);
        }
        return $queryBuilder;
    }

    public function configureActions(Actions $actions): Actions
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $actions->setPermissions([Action::INDEX, Action::NEW, Action::EDIT, Action::DELETE])
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Créer un utilisateur'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, fn (Action $action) => $action->setLabel('Créer et ajouter un nouvel utilisateur'));
        } else if ($this->isGranted('ROLE_REDACTEUR')) {
            $actions->setPermissions([Action::INDEX, Action::EDIT])->disable(Action::NEW)->disable(Action::DELETE);
        }
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {    
        $username = TextField::new('username')->setLabel('Nom d\'utilisateur');        
        $isVerifiedField = BooleanField::new('isVerified')->setLabel('Utilisateur vérifié')->onlyOnIndex();
        $isEnable2faField = BooleanField::new('isEnable2fa')->setLabel('Double authentification')->hideWhenCreating();

        if ($pageName === Crud::PAGE_INDEX) {
            $isVerifiedField->setDisabled();
            $isEnable2faField->setDisabled();
        } else if ($pageName === Crud::PAGE_EDIT) {
            $userEdit = $this->getContext()->getEntity()->getInstance();
            $userEdit2fa = $userEdit->isEnable2fa();
            if ($this->getUser() !== $userEdit && !$userEdit2fa)
                $isEnable2faField->setDisabled();
        }

        return [
            $username,
            ChoiceField::new('roles')
                ->setLabel('Rôle')
                ->setChoices(['Administrateur' => 'ROLE_ADMIN', 'Rédacteur' => 'ROLE_REDACTEUR'])
                ->allowMultipleChoices(true)
                ->setRequired(true),
            TextField::new('email'),
            TextField::new('password')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'Mot de passe', 'hash_property_path' => 'password'],
                    'second_options' => ['label' => 'Répéter le mot de passe'],
                    'mapped' => false,
                    'constraints' => [
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                    ],
                ])
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->onlyOnForms(),
            $isVerifiedField,
            $isEnable2faField
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof User){
            // Envoie d'un email de confirmation au nouvel utilisateur
            $this->sendMail($entityInstance);
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User){
            // Activer/Désactiver la double authentification
            if ($entityInstance->isEnable2fa() && !$entityInstance->isTotpAuthenticationEnabled()) {
                $totpSecret = $this->totpAuthenticatorInterface->generateSecret();
                $entityInstance->setTotpAuthenticationSecret($totpSecret);
                $this->addFlash('success', 'Double authentification activé pour ' . $entityInstance->getUsername() . ', veuillez télécharger une application d\'authentification et scanner votre QR Code');
            } elseif (!$entityInstance->isEnable2fa() && $entityInstance->isTotpAuthenticationEnabled()) {
                $totpSecret = null;
                $entityInstance->setTotpAuthenticationSecret($totpSecret);
                $this->addFlash('success', 'Double authentification désactivé pour ' . $entityInstance->getUsername());
            } else {
                return;
            }
        }
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function sendMail(UserInterface $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('contact@veille-seo.cleatis.fr', 'Cleatis'))
            ->to($user->getEmail())
            ->subject('Veille SEO CLEATIS - Veuillez confirmer votre e-mail')
            ->htmlTemplate('registration/confirmation_email.html.twig');

        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, $email);

        $this->addFlash('success', 'Le compte de ' . $user->getUsername() . ' a été créé, il doit à présent confirmer son adresse e-mail.');
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    #[IsGranted('ROLE_REDACTEUR')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_logout');
        }

        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée.');

        return $this->redirectToRoute('admin');
    }
}
