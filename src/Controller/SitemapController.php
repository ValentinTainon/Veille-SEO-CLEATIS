<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findBy([], ['publicationDate' => 'DESC']);

        $urls = [];

        $urls[] = ['loc' => $this->generateUrl('app_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL)];
        $urls[] = ['loc' => $this->generateUrl('app_auteurs', [], UrlGeneratorInterface::ABSOLUTE_URL)];

        foreach ($articles as $article) {
            $urls[] = [
                'loc' => $this->generateUrl('app_article_show', ['slug' => $article->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => $article->getPublicationDate()->format('d-m-Y'),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];
        }

        $response = new Response($this->renderView('./sitemap/sitemap.html.twig', ['urls' => $urls]), 200);

        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
}
