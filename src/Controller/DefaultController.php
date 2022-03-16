<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    //#[Route("/", name:"default_home", methods:["GET"])]
    /**
     * @Route("/", name="default_home", methods={"GET"})
     */
    public function home(): Response
    {
        return $this->render('default/home.html.twig');
    }
} 