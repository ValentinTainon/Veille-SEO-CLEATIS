<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\FluxRssRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(FluxRssRepository $fluxRssRepository, ArticleRepository $articleRepository, Request $request, PaginatorInterface $paginator): Response
    {
        // Récupération de tous les liens RSS présent dans la BDD.
        $liensRss = $fluxRssRepository->findAll();

        // Récupérations de tout les articles présent dans la BDD et de leur titre.
        $articles = $articleRepository->findAll();
        $titresExistants = [];
        foreach ($articles as $article) {
            $titresExistants[] = $article->getTitre();
        }

        // La première boucle foreach charge chaque lien RSS et regroupe les articles (items) qu'il contient dans un tableau.
        foreach ($liensRss as $lienRss) {
            $fluxRss = simplexml_load_file($lienRss->getLienRss());
            $items = $fluxRss->channel->item;

            // La deuxième boucle foreach parcours chaque article (item) contenu dans le tableau d'articles (items).
            foreach ($items as $item) {
                // Vérification si l'article existe déjà.
                $titre = (string)$item->title;
                if (in_array($titre, $titresExistants)) {
                    continue;
                }

                // Si l'article n'existe pas, création d'un nouvel article, et l'enregistrer dans la BDD.
                $nouvelArticle = new Article();
                $channel = $fluxRss->channel;
                $datePublication = (new \DateTime($item->pubDate));
                $nomSource = $channel->title;
                $lienSource = $channel->link;
                $lienArticle = $item->link;

                $nouvelArticle->setDatePublication($datePublication)
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
                            $description = '';
                        
                        $nouvelArticle->setDescription($description);
                    }
                }
                $articleRepository->save($nouvelArticle, true);
            }
        }

        /* PAGINATION */
        $pagination = $paginator->paginate(
            $articleRepository->paginationQuery(),
            $request->query->get('page', 1), /* Numéro de page */
            10 /* Limite par page */
        );

        /* VIDÉOS YOUTUBE */
        // Récupérer l'identifiant de la chaîne YouTube
        $channelId = 'UCy1mcM9zvIcbhspqRrIjIbw';

        // Récupérer la clé d'API YouTube Data
        $apiKey = 'AIzaSyBg9qWByyHJa55BIVgxge1PGmIew0BNPKY';

        // Construire l'URL de l'API pour récupérer les dernières vidéos de la chaîne
        $url = sprintf('https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=%s&order=date&maxResults=3&key=%s', $channelId, $apiKey);

        // Envoyer une requête HTTP à l'API YouTube Data pour récupérer les dernières vidéos de la chaîne
        $client = HttpClient::create();
        $response = $client->request('GET', $url);

        // Vérifier si la requête a réussi
        if ($response->getStatusCode() !== 200)
            throw new BadRequestHttpException('La requête a échoué.');

        // Analyser la réponse JSON de l'API YouTube Data
        $data = json_decode($response->getContent(), true);

        // Vérifier si des vidéos ont été trouvées
        if (!isset($data['items']))
            throw new NotFoundHttpException('Aucune vidéo trouvée.');

        // Parcourir les 3 dernières vidéos et afficher leurs balises iframe
        $iframes = [];
        for ($i = 0; $i < 3; $i++) {
            $item = $data['items'][$i];
            $iframes[$i] = sprintf('<iframe loading="lazy" src="https://www.youtube.com/embed/%s" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>', $item['id']['videoId']);
        }

        return $this->render('main/homepage.html.twig', [
            'pagination' => $pagination,
            'iframes' => $iframes,
        ]);
    }

    #[Route('/auteurs', name: 'app_auteurs')]
    public function auteurs(): Response
    {
        return $this->render('main/auteurs.html.twig');
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article, ArticleRepository $articleRepository): Response
    {
        /* LES 10 DERNIERS ARTICLES */
        $derniersArticles = $articleRepository->findBy([], ['datePublication' => 'desc'], 10);

        /* VIDÉOS YOUTUBE */
        // Récupérer l'identifiant de la chaîne YouTube
        $channelId = 'UCy1mcM9zvIcbhspqRrIjIbw';

        // Récupérer la clé d'API YouTube Data
        $apiKey = 'AIzaSyBg9qWByyHJa55BIVgxge1PGmIew0BNPKY';

        // Construire l'URL de l'API pour récupérer les dernières vidéos de la chaîne
        $url = sprintf('https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=%s&order=date&maxResults=3&key=%s', $channelId, $apiKey);

        // Envoyer une requête HTTP à l'API YouTube Data pour récupérer les dernières vidéos de la chaîne
        $client = HttpClient::create();
        $response = $client->request('GET', $url);

        // Vérifier si la requête a réussi
        if ($response->getStatusCode() !== 200)
            throw new BadRequestHttpException('La requête a échoué.');

        // Analyser la réponse JSON de l'API YouTube Data
        $data = json_decode($response->getContent(), true);

        // Vérifier si des vidéos ont été trouvées
        if (!isset($data['items']))
            throw new NotFoundHttpException('Aucune vidéo trouvée.');

        // Parcourir les 3 dernières vidéos et afficher leurs balises iframe
        $iframes = [];
        for ($i = 0; $i < 3; $i++) {
            $item = $data['items'][$i];
            $iframes[$i] = sprintf('<iframe loading="lazy" src="https://www.youtube.com/embed/%s" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>', $item['id']['videoId']);
        }

        return $this->render('main/show.html.twig', [
            'article' => $article,
            'derniersArticles' => $derniersArticles,
            'iframes' => $iframes,
        ]);
    }
}
