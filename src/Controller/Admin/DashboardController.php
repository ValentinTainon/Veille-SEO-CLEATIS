<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\RssFeed;
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

        $url = $routeBuilder->setController(UserCrudController::class)->generateUrl();
        
        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setFaviconPath("images/favicon.png")->setTitle('Veille Seo Cleatis');
    }

    public function configureMenuItems(): iterable
    {
        if ($this->getUser()->isTotpAuthenticationEnabled()){
            yield MenuItem::linkToRoute('Afficher mon QR Code', 'fa-sharp fa-solid fa-qrcode', 'qr_code_totp');
        }
        
        yield MenuItem::linkToCrud('Mon profil', 'fas fa-user', User::class)->setController(UserProfileCrudController::class);
        
        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class)->setController(UserCrudController::class);
            yield MenuItem::linkToCrud('Flux RSS', 'fas fa-rss', RssFeed::class);
        }

        yield MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Article::class);
        yield MenuItem::linkToUrl('Retour sur le site', 'fas fa-home', '/');
    }
}
