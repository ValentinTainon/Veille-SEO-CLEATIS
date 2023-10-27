<?php

namespace App\Services;

use App\Entity\Article;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use App\Repository\ArticleRepository;
use App\Repository\RssFeedRepository;
use Symfony\Component\Mailer\MailerInterface;

class ManageArticlesGeneratedByRssFeedEvent
{
    public function __construct(){}

    public function manageArticlesGeneratedByRssFeed(ArticleRepository $articleRepository, RssFeedRepository $rssFeedRepository, MailerInterface $mailer): void
    {
        // Récupérations de tout les articles présent dans la BDD ainsi que de leur titre.
        $articlesInDatabase = $articleRepository->findAll();
        $titlesInDatabase = [];
        foreach ($articlesInDatabase as $article) {
            $titlesInDatabase[] = $article->getTitle();
        }
        
        // Récupération de tous les Flux RSS présent dans la BDD.
        $rssFeeds = $rssFeedRepository->findAll();

        // Initialisation d'un tableau qui regroupera les titres d'articles présent dans le fichier XML de chaque Flux RSS.
        $titlesInRssFeeds = [];

        // La première boucle foreach charge le fichier XML de chaque Flux RSS et regroupe les articles qu'il contient dans un tableau.
        foreach ($rssFeeds as $rssFeed) {
            $xmlFile = simplexml_load_file($rssFeed->getXmlLink());
            $items = $xmlFile->channel->item;
            
            // La deuxième boucle foreach parcours chaque article contenu dans le tableau d'articles du fichier XML actuel.
            foreach ($items as $item) {
                // Remplissage du tableau $titlesInRssFeeds avec les titres présent dans les articles du fichier XML actuel.
                $title = (string)$item->title;
                $titlesInRssFeeds[] = $title;

                // On vérifie si le flux RSS est valide, si ce n'est pas le cas on le supprime de la base de données et on passe directement au prochain tour de boucle.
                if ($title === 'RSS is deleted') {
                    $rssFeedRepository->remove($rssFeed, true);

                    $email = (new Email())
                    ->from(new Address('contact@cleatis.fr', 'https://100-referencement.com'))
                    ->to(new Address('contact@cleatis.fr', 'Cleatis'))
                    ->subject('Veille SEO Cleatis - Flux RSS invalide')
                    ->html('Le flux RSS suivant : <p style="color:red;">' . $rssFeed->getXmlLink() . '</p> n\'est plus valide, il a donc été supprimé de la base de données.');

                    $mailer->send($email);

                    continue;
                }

                // On vérifie si l'article existe déjà dans la base de données, si c'est le cas on passe directement au prochain tour de boucle.
                else if (in_array($title, $titlesInDatabase)) {
                    continue;
                }
                
                // Si l'article n'existe pas, création d'un nouvel article, définir ses propriétés, et l'enregistrer dans la base de données.
                $newArticle = new Article();

                $newArticle->setRssFeed($rssFeed)
                    ->setPublicationDate(new \DateTime($item->pubDate))
                    ->setSource($xmlFile->channel->title)
                    ->setSourceLink($xmlFile->channel->link)
                    ->setTitle($title)
                    ->setArticleLink($item->link)
                    ->setImageName($xmlFile->channel->image->url)
                    ->setImageAlt($xmlFile->channel->image->title);

                if ($item->children('content', true)->encoded) {
                    $newArticle->setDescription($item->children('content', true)->encoded);
                } else {
                    $newArticle->setDescription($item->description);
                }
                
                $articleRepository->save($newArticle, true);
            }
        }

        // Supression des articles anciens qui ne sont plus présent dans les Flux RSS.
        // foreach ($articlesInDatabase as $article) {
        //     if ($article->getRssFeed() && !in_array($article->getTitle(), $titlesInRssFeeds))
        //         $articleRepository->remove($article, true);
        // }
    }
}