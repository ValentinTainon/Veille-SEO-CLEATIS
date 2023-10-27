<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use App\Security\EmailVerifier;
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
use Symfony\Component\Validator\Constraints\Regex;
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
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private TotpAuthenticatorInterface $totpAuthenticatorInterface, private AuthorizationCheckerInterface $authorizationChecker, private EntityManagerInterface $entityManager, private UserPasswordHasherInterface $userPasswordHasher, private EmailVerifier $emailVerifier)
    {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle(Crud::PAGE_INDEX, 'Gestion des utilisateurs')
                    ->setPageTitle(Crud::PAGE_NEW, 'Créer un utilisateur')
                    ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'utilisateur');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->where('entity.id != :userId')->setParameter('userId', $this->getUser()->getId());
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Créer un utilisateur'))
                    ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, fn (Action $action) => $action->setLabel('Créer et ajouter un nouvel utilisateur'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('username', 'Nom d\'utilisateur')->setDisabled($pageName === Crud::PAGE_EDIT)->setRequired($pageName === Crud::PAGE_NEW);
        yield ChoiceField::new('roles')
            ->setLabel($pageName === Crud::PAGE_INDEX ? 'Rôle' : 'Rôle (Veuillez selectionner un seul rôle !)')
            ->setChoices(['Administrateur' => 'ROLE_ADMIN', 'Rédacteur' => 'ROLE_REDACTEUR'])
            ->allowMultipleChoices(true);
        yield TextField::new('email')->setDisabled($pageName === Crud::PAGE_EDIT)->setRequired($pageName === Crud::PAGE_NEW);
        yield TextField::new('password')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Mot de passe', 'hash_property_path' => 'password'],
                'second_options' => ['label' => 'Répéter le mot de passe'],
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
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyWhenCreating();
        yield BooleanField::new('isVerified', 'Utilisateur vérifié')->onlyOnIndex()->setDisabled();
            
        $userEdit = $this->getContext()->getEntity()->getInstance();
        if ($pageName === Crud::PAGE_EDIT && $userEdit->isEnable2fa()) {
            yield BooleanField::new('isEnable2fa', 'Désactiver sa double authentification (en cas d\'erreur de l\'utilisateur concerné)')->setDisabled(false);
        } else {
            yield BooleanField::new('isEnable2fa', 'Double authentification')->hideWhenCreating()->setDisabled();
        }

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
            // Désactiver la double authentification
            if (!$entityInstance->isEnable2fa() && $entityInstance->isTotpAuthenticationEnabled()) {
                $totpSecret = null;
                $entityInstance->setTotpAuthenticationSecret($totpSecret);
                $this->addFlash('success', 'Double authentification désactivé pour ' . $entityInstance->getUsername());
            }
        }
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function sendMail(UserInterface $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('contact@cleatis.fr', 'Cleatis'))
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

            return $this->redirectToRoute('app_homepage');
        }

        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée.');

        return $this->redirectToRoute('admin');
    }
}
