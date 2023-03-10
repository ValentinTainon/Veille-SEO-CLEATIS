<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\FluxRss;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $routeBuilder = $this->container->get(AdminUrlGenerator::class);
        if ($this->isGranted('ROLE_REDACTEUR'))
            $url = $routeBuilder->setController(ArticleCrudController::class)->generateUrl();
        if ($this->isGranted('ROLE_ADMIN'))
            $url = $routeBuilder->setController(UserCrudController::class)->generateUrl();

        return $this->redirect($url);

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Veille Seo Cleatis');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoRoute('Retour sur le site', 'fas fa-home', 'app_home');
        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class)
                ->setPermission('ROLE_ADMIN');
            yield MenuItem::linkToCrud('Flux RSS', 'fas fa-rss', FluxRss::class)
                ->setPermission('ROLE_ADMIN');
        }
        if ($this->isGranted('ROLE_REDACTEUR'))
            yield MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Article::class);
    }
}
