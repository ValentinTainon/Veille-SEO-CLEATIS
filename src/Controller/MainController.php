<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\SearchFormType;
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
    public function index(YoutubeVideoRepository $youtubeVideoRepository, Request $request, PaginatorInterface $paginator, ArticleRepository $articleRepository): Response
    {
        /* VIDÉOS YOUTUBE */
        // Récupérations de toutes les vidéo Youtube présent dans la base de données ainsi que de leur iframes.
        $allYoutubeVideoBdd = $youtubeVideoRepository->findAll();
        $iframes = [];
        foreach ($allYoutubeVideoBdd as $youtubeVideoBdd) {
            $iframes[] = $youtubeVideoBdd->getIframe();
        }

        /* SEARCH */
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $query = $form->get('query')->getData();
            
            /* PAGINATION DES ARTICLES RECHERCHE */
            $articles = $paginator->paginate(
                $articleRepository->findBySearch($query),
                $request->query->get('page', 1), /* Numéro de page */
                10 /* Limite par page */
            );

            return $this->render('main/homepage.html.twig', [
                'form' => $form,
                'articles' => $articles,
                'iframes' => $iframes,
            ]);
        }

        /* PAGINATION DE TOUT LES ARTICLES */
        $articles = $paginator->paginate(
            $articleRepository->findBy([], ['datePublication' => 'desc']),
            $request->query->get('page', 1), /* Numéro de page */
            10 /* Limite par page */
        );

        return $this->render('main/homepage.html.twig', [
            'form' => $form,
            'articles' => $articles,
            'iframes' => $iframes,
        ]);
    }
    
    #[Route('/actualite/{slug}', name: 'app_article_show', methods: ['GET'])]
    public function show(YoutubeVideoRepository $youtubeVideoRepository, Request $request, PaginatorInterface $paginator, ArticleRepository $articleRepository, Article $article): Response
    {
        /* VIDÉOS YOUTUBE */
        // Récupérations de toutes les vidéo Youtube présent dans la base de données ainsi que de leur iframes.
        $allYoutubeVideoBdd = $youtubeVideoRepository->findAll();
        $iframes = [];
        foreach ($allYoutubeVideoBdd as $youtubeVideoBdd) {
            $iframes[] = $youtubeVideoBdd->getIframe();
        }
        
        /* SEARCH */
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $query = $form->get('query')->getData();
            
            /* PAGINATION DES ARTICLES RECHERCHE */
            $articles = $paginator->paginate(
                $articleRepository->findBySearch($query),
                $request->query->get('page', 1), /* Numéro de page */
                10 /* Limite par page */
            );

            return $this->render('main/homepage.html.twig', [
                'form' => $form,
                'articles' => $articles,
                'iframes' => $iframes,
            ]);
        }
        
        /* NAVIGATION DES ARTICLES PRECEDENT/SUIVANT */
        $articlePrecedent = $articleRepository->articlePrecedentQuery($article);
        $articleSuivant = $articleRepository->articleSuivantQuery($article);
        
        /* LES 10 DERNIERS ARTICLES */
        $derniersArticles = $articleRepository->findBy([], ['datePublication' => 'DESC'], 10);
        
        return $this->render('main/show.html.twig', [
            'form' => $form,
            'article' => $article,
            'articlePrecedent' => $articlePrecedent,
            'articleSuivant' => $articleSuivant,
            'derniersArticles' => $derniersArticles,
            'iframes' => $iframes,
        ]);
    }

    #[Route('/auteurs', name: 'app_auteurs')]
    public function auteurs(): Response
    {
        return $this->render('main/auteurs.html.twig');
    }
}
