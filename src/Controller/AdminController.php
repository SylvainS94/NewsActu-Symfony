<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminController extends AbstractController
{    
    #[Route("/admin/tableau-de-bord", name:"show_dashboard", methods:["GET"])]
    public function showDashboard(EntityManagerInterface $entityManager): Response 
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();
        
        return $this->render('admin/show_dashboard.html.twig', [
            'articles'=> $articles,
        ]); // redirection dans templates (voir en bas)
    }

    #[Route("/admin/creer-un-article", name:"create_article", methods:["GET|POST"])]
    public function createArticle(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
       $article = new Article();

       $form = $this->createForm(ArticleFormType::class, $article)
           ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            // Pour accéder à une valeur d'un input de $form, on fait :
                // $form->get('title')->getData()
            $article->setAlias($slugger->slug($article->getTitle()) ); 
            $article->setCreatedAt(new DateTime());
            $article->setUpdatedAt(new Datetime());
            
            // Variabilisation du fichier 'photo' uploadé.
            $file = $form->get('photo')->getData();
            
            // if (isset($file) === true) = Si un fichier est uploadé (depuis le formulaire)
            if($file) {
                // On reconstruit le nom du fichier pour le sécuriser
                
                // 1ere Etape = on deconstruit le nom du fichier et on variabilise.
                $extension = '.' . $file->guessExtension();  // on rajoute '.' pour inserer avec extension jpg = .jpg
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                
                // Assainissement du nom de fichier(filename)
                //$safeFilename = $slugger->slug($originalFilename); // Pas d'espace ou d'accents (remplacé par des -)
                $safeFilename = $article->getAlias(); // on choisi d'indiquer les alias et pas l'originalFilename (choix perso)
                // 2eme Etape : On reconstruit le nom du fichier maintenant qu'il est safe.
                // uniqid cree une id unique pour chaque photo + option en arg (prefix:"", more_entropy:true) = rajouter un second id en +
                $newFilename =  $safeFilename . '_' . uniqid() . $extension; 

                // try/catch = PHP natif pour methodes avec throw (lancer)
                try { // Envoie
                    // On a configuré avant un parametre 'uploads_dir' dans services.yaml (dans config/routes)
                    // Ce parametre contient le chemin absolu de notre dossier d'upload de photo
                    $file->move($this->getParameter('uploads_dir'), $newFilename);

                    // On set le NOM de la photo, pas le CHEMIN
                    $article->setPhoto($newFilename);

                } catch (FileException $exception) { // si erreur de type , rattrape FileException avec un objet $exception et n'affiche pas l'erreur
                    
                } // END catch()
            } // END if ($file)
           
            $entityManager->persist($article);
            $entityManager->flush();

            // Ici on ajoute un message qu'on affichera en twig (dans templates)
            $this->addFlash('success', 'Bravo, votre article est bien en ligne !');
            
            return $this->redirectToRoute('show_dashboard'); // redirection
        } // END if ($form)

        return $this->render('admin/form/create_article.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
