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
        return Dashboard::new()->setTitle('Veille Seo Cleatis');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoRoute('Retour sur le site', 'fas fa-home', 'app_homepage');

        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class)->setPermission('ROLE_ADMIN');
            yield MenuItem::linkToCrud('Flux RSS', 'fas fa-rss', FluxRss::class)->setPermission('ROLE_ADMIN');
        }
        if ($this->isGranted('ROLE_REDACTEUR'))
            yield MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Article::class)->setPermission('ROLE_REDACTEUR');
    }
}
