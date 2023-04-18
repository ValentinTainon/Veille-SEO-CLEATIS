<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\YoutubeVideoRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(PaginatorInterface $paginator, ArticleRepository $articleRepository, Request $request, YoutubeVideoRepository $youtubeVideoRepository): Response
    {
        /* PAGINATION */
        $pagination = $paginator->paginate(
            $articleRepository->findBy([], ['datePublication' => 'desc']),
            $request->query->get('page', 1), /* Numéro de page */
            10 /* Limite par page */
        );

        /* VIDÉOS YOUTUBE */
        // Récupérations de toutes les vidéo Youtube présent dans la base de données ainsi que de leur iframes.
        $allYoutubeVideoBdd = $youtubeVideoRepository->findAll();
        $iframes = [];
        foreach ($allYoutubeVideoBdd as $youtubeVideoBdd) {
            $iframes[] = $youtubeVideoBdd->getIframe();
        }

        return $this->render('main/homepage.html.twig', [
            'articles' => $pagination,
            'iframes' => $iframes,
        ]);
    }

    #[Route('/auteurs', name: 'app_auteurs')]
    public function auteurs(): Response
    {
        return $this->render('main/auteurs.html.twig');
    }

    #[Route('/actualite/{slug}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article, ArticleRepository $articleRepository, YoutubeVideoRepository $youtubeVideoRepository): Response
    {
        /* PAGINATION */
        $articlePrecedent = $articleRepository->articlePrecedentQuery($article);
        $articleSuivant = $articleRepository->articleSuivantQuery($article);
        
        /* LES 10 DERNIERS ARTICLES */
        $derniersArticles = $articleRepository->findBy([], ['datePublication' => 'DESC'], 10);

        /* VIDÉOS YOUTUBE */
        // Récupérations de toutes les vidéo Youtube présent dans la base de données ainsi que de leur iframes.
        $allYoutubeVideoBdd = $youtubeVideoRepository->findAll();
        $iframes = [];
        foreach ($allYoutubeVideoBdd as $youtubeVideoBdd) {
            $iframes[] = $youtubeVideoBdd->getIframe();
        }

        return $this->render('main/show.html.twig', [
            'article' => $article,
            'articlePrecedent' => $articlePrecedent,
            'articleSuivant' => $articleSuivant,
            'derniersArticles' => $derniersArticles,
            'iframes' => $iframes,
        ]);
    }
}
