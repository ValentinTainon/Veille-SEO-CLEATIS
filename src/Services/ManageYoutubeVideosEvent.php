<?php

namespace App\Services;

use App\Entity\YoutubeVideo;
use App\Repository\YoutubeVideoRepository;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ManageYoutubeVideosEvent
{
    public function __construct(){}

    public function ManageYoutubeVideos(YoutubeVideoRepository $youtubeVideoRepository): void
    {
        // Récupérations de toutes les vidéo Youtube présent dans la base de données ainsi que de leur iframes.
        $allYoutubeVideoBdd = $youtubeVideoRepository->findAll();
        $iframesExistantsBdd = [];
        foreach ($allYoutubeVideoBdd as $youtubeVideoBdd) {
            $iframesExistantsBdd[] = $youtubeVideoBdd->getIframe();
        }

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

        // Parcourir les 3 dernières vidéos Youtube et stocker leurs balises iframe dans un tableau
        $iframes = [];
        for ($i = 0; $i < 3; $i++) {
            $video = $data['items'][$i];
            $iframes[$i] = sprintf('<iframe loading="lazy" src="https://www.youtube.com/embed/%s" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>', $video['id']['videoId']);
            
            // Vérifier si la vidéo Youtube existe déjà dans la base de données, si c'est le cas on passe directement au prochain tour de boucle.
            if (in_array($iframes[$i], $iframesExistantsBdd))
            continue;
        
            // Si la vidéo Youtube n'existe pas, création d'une nouvelle vidéo Youtube, et l'enregistrer dans la base de données.
            $newYoutubeVideo = new YoutubeVideo();
            $newYoutubeVideo->setIframe($iframes[$i]);
            $youtubeVideoRepository->save($newYoutubeVideo, true);
        }

        // Supression des anciennes vidéos Youtube de la base de données.
        foreach ($allYoutubeVideoBdd as $youtubeVideoBdd) {
            if (!in_array($youtubeVideoBdd->getIframe(), $iframes))
                $youtubeVideoRepository->remove($youtubeVideoBdd, true);
        }
    }
}
