<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('main/home.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }

    #[Route('/auteurs', name: 'app_auteurs')]
    public function actualites(): Response
    {
        return $this->render('main/auteurs.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }
}
