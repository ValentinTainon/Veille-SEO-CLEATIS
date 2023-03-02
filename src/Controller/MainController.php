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
        // $article = new Article();

        $rss = simplexml_load_file('http://fetchrss.com/rss/63f8b191955a4765077b629263f8b17e790a150722707622.xml');
        $rssNoiise = simplexml_load_file('https://www.noiise.com/feed');
        $rssAbondance = simplexml_load_file('https://www.abondance.com/feed');
        $rssNeper = simplexml_load_file('https://www.neper.fr/feed');
        
        foreach($rssNoiise->children() as $child)
        {
            $child->getName() . ": " . $child . "<br>";
        }

        // $datePublication = $rssNoiise->get('pubDate')->getData();

        // $article->setDatePublication($datePublication);

        // $articleRepository->save($article, true);

        return $this->render('main/home.html.twig', [
            'articles' => $articleRepository->findAll(),
            'rss' => $rss,
            'rssNoiise' => $rssNoiise,
            'rssAbondance' => $rssAbondance,
            'rssNeper' => $rssNeper,
            'child' => $child,
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
