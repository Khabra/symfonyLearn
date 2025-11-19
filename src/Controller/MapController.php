<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MapController extends AbstractController
{
    #[Route('/campus-map', name: 'app_campus_map')]
    public function index(): Response
    {
        return $this->render('map/campus_map.html.twig');
    }
}
