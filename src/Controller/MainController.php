<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\FluxRssRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(FluxRssRepository $fluxRssRepository, ArticleRepository $articleRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $liensRss = $fluxRssRepository->findAll();

        foreach ($liensRss as $element) {
            $fluxRss = simplexml_load_file($element->getLienRss());

            foreach ($fluxRss->channel->item as $item) {
                $nouvelArticle = new Article();
                $titre = $item->title;
                $articlesBdd = $articleRepository->findAll();
                $existant = false;

                $nouvelArticle->setTitre($titre);

                foreach ($articlesBdd as $articleBdd) {
                    if ($nouvelArticle->getTitre() === $articleBdd->getTitre()) {
                        $existant = true;
                        break;
                    }
                }

                if (!$existant) {
                    $datePublication = (new \DateTime($item->pubDate));
                    $nomSource = $fluxRss->channel->image->title;
                    $lienSource = $fluxRss->channel->image->link;
                    $description = $item->description;
                    $content = (string)$item->children("content", true)->encoded;
                    $lienArticle = $item->link;

                    $nouvelArticle->setDatePublication($datePublication)
                        ->setNomSource($nomSource)
                        ->setLienSource($lienSource)
                        ->setTitre($titre)
                        ->setLienArticle($lienArticle);

                    if (!$content)
                        $nouvelArticle->setDescription($description);
                    else
                        $nouvelArticle->setDescription($content);

                    $articleRepository->save($nouvelArticle, true);
                }
            }
        }

        /* PAGINATION */
        $pagination = $paginator->paginate(
            $articleRepository->paginationQuery(),
            $request->query->get('page', 1), /* Numéro de page */
            10 /* Limite par page */
        );

        return $this->render('main/home.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('main/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/auteurs', name: 'app_auteurs')]
    public function auteurs(): Response
    {
        return $this->render('main/auteurs.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }
}
