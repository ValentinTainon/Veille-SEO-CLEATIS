<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuteursController extends AbstractController
{
    #[Route('/auteurs', name: 'app_auteurs')]
    public function auteurs(): Response
    {
        return $this->render('main/auteurs.html.twig');
    }
}
