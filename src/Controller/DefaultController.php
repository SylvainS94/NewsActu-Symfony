<?php 

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    //#[Route("/", name:"default_home", methods:["GET"])]
    /**
     * @Route("/", name="default_home", methods={"GET"})
     */
    public function home(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findBy(['deletedAt' => null]);

        return $this->render('default/home.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
    * @Route("/categories", name="render_categories_in_nav", methods={"GET"})
    */
    public function renderCategoriesInNav(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Categorie::class)->findBy(['deletedAt' => null]);

        return $this->render('rendered/nav_categories.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
    * @Route("/recherche", name="render_search_bar", methods={"GET|POST"})
    */
    public function renderSearchBarInNav(): Response
    {
        $form =$this->createForm(SearchFormType::class)->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $query->query = $form->get('query')->getData();
        }

        return $this->render('rendered/search_bar.html.twig', [
            
            'form' => $form->createView()
         ]);
    }
} 