<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\FluxRss;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $routeBuilder = $this->container->get(AdminUrlGenerator::class);

        if ($this->isGranted('ROLE_ADMIN'))
            $url = $routeBuilder->setController(UserCrudController::class)->generateUrl();
        else if ($this->isGranted('ROLE_REDACTEUR'))
            $url = $routeBuilder->setController(ArticleCrudController::class)->generateUrl();
        
        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        $username = $this->getUser()->getUsername();

        return Dashboard::new()->setFaviconPath("images/favicon.png")->setTitle('Veille Seo Cleatis - ' . $username);
    }

    public function configureMenuItems(): iterable
    {
        $userId = $this->getUser()->getId();

        yield MenuItem::linkToUrl('Retour sur le site', 'fas fa-home', '/');

        if ($this->isGranted('ROLE_REDACTEUR') && $this->getUser()->isTotpAuthenticationEnabled()){
            yield MenuItem::linkToRoute('Afficher mon QR Code', 'fa-sharp fa-solid fa-qrcode', 'qr_code_totp')
            ->setPermission('ROLE_REDACTEUR');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class)->setPermission('ROLE_ADMIN');
            yield MenuItem::linkToCrud('Flux RSS', 'fas fa-rss', FluxRss::class)->setPermission('ROLE_ADMIN');
            yield MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Article::class)->setPermission('ROLE_ADMIN');
        } else if ($this->isGranted('ROLE_REDACTEUR')){
            yield MenuItem::linkToCrud('Utilisateur', 'fas fa-user', User::class)->setEntityId($userId)->setPermission('ROLE_REDACTEUR');
            yield MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Article::class)->setPermission('ROLE_REDACTEUR');
        }
    }
}
