<?php

namespace App\Services;

use DateTime;
use App\Entity\Article;
use Cocur\Slugify\Slugify;
use App\Repository\ArticleRepository;
use App\Repository\FluxRssRepository;

class ManageArticlesGeneratedByRssFeedEvent
{
    public function __construct(){}

    public function manageArticlesGeneratedByRssFeed(ArticleRepository $articleRepository, FluxRssRepository $fluxRssRepository): void
    {
        // Récupérations de tout les articles présent dans la BDD ainsi que de leur titre.
        $articles = $articleRepository->findAll();
        $titresExistantsBdd = [];
        foreach ($articles as $article) {
            $titresExistantsBdd[] = $article->getTitre();
        }
        
        // Récupération de tous les liens RSS présent dans la BDD.
        $liensRss = $fluxRssRepository->findAll();

        // Initialisation d'un tableau regroupant les titres d'articles présent dans les Flux RSS.
        $titresExistantsRss = [];

        // La première boucle foreach charge chaque lien RSS et regroupe les articles (items) qu'il contient dans un tableau.
        foreach ($liensRss as $lienRss) {
            $fluxRss = simplexml_load_file($lienRss->getLienRss());
            $items = $fluxRss->channel->item;
            
            // La deuxième boucle foreach parcours chaque article (item) contenu dans le tableau d'articles (items).
            foreach ($items as $item) {
                // Remplissage du tableau des titres d'articles présent dans les liensRSS.
                $titre = (string)$item->title;
                $titresExistantsRss[] = $titre;

                // Vérification si l'article existe déjà dans la base de données, si c'est le cas on passe directement au prochain tour de boucle.
                if (in_array($titre, $titresExistantsBdd))
                    continue;
                
                // Si l'article n'existe pas, création d'un nouvel article, et l'enregistrer dans la base de données.
                $nouvelArticle = new Article();
                $channel = $fluxRss->channel;
                $fluxRssId = $fluxRssRepository->findOneBy(['id' => $lienRss->getId()]);
                $datePublication = (new DateTime($item->pubDate));
                $nomSource = $channel->title;
                $lienSource = $channel->link;
                $lienArticle = $item->link;

                $nouvelArticle->setFluxRss($fluxRssId)
                    ->setDatePublication($datePublication)
                    ->setNomSource($nomSource)
                    ->setLienSource($lienSource)
                    ->setTitre($titre)
                    ->setLienArticle($lienArticle);

                if ($item->children("content", true)->encoded) {
                    $description = $item->children("content", true)->encoded;
                    $nouvelArticle->setDescription($description);
                } else {
                    $fullDescription = $item->description;
                    preg_match('/(?:<p>(.*?)<\/p>|<span[^>]*>(.*?)<\/span>|([^<>]+))/', $fullDescription, $matches);

                    if (array_key_exists(0, $matches)) {
                        $description = $matches[0];
                        if ($description === $titre)
                            $description = null;
                        
                        $nouvelArticle->setDescription($description);
                    }
                }
                $articleRepository->save($nouvelArticle, true);
            }
        }

        // // Supression des articles anciens qui ne sont plus présent dans les flux RSS.
        // foreach ($articles as $article) {
        //     if (!in_array($article->getTitre(), $titresExistantsRss))
        //         $articleRepository->remove($article, true);
        // }
    }
}
