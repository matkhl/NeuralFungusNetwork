<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BaseController extends AbstractController
{
    #[Route('', 'index', methods: 'GET')]
    public function indexAction(): Response
    {
        return $this->render('index.html.twig');
    }
}