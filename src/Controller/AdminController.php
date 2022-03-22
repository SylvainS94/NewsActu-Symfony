<?php

namespace App\Controller;

use DateTime;
use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\User;
use App\Form\ArticleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{    
    //#[Route("/admin/tableau-de-bord", name:"show_dashboard", methods:["GET"])]
    /**
     * @Route("/tableau-de-bord", name="show_dashboard", methods={"GET"})
     * // IsGranted("ROLE_ADMIN") ici cest une autre solution que denyAccessUnlessGranted dans fonction showDashboard ou que modifier le yaml security
     */
    public function showDashboard(EntityManagerInterface $entityManager): Response 
    {
        // try/catch fait parti de PHP nativement, il permet de gérer les class Exception (erreur).
        // On se sert d'un try/catch lorqu'on utilise des méthodes (fonctions) QUI LANCENT (throw) une Exception
        // Si la méthode lance l'erreur pendant son éxécution, alors l'Exception sera 'attrapéé' (catch).
        // Le code dans les accolades du catch sera alors éxécuté.
        try{
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }
        catch(AccessDeniedException $exception) {
            $this->addFlash('warning', 'Cette partie du site est réservée.');
            return $this->redirectToRoute('default_home');
        }
        
        $articles = $entityManager->getRepository(Article::class)->findAll();
        $categories = $entityManager->getRepository(Categorie::class)->findAll();
        $users =$entityManager->getRepository(User::class)->findAll();

        
        return $this->render('admin/show_dashboard.html.twig', [
            'articles'=> $articles,
            'categories' => $categories,
            'users' => $users,
        ]); // redirection dans templates (voir en bas)
    }

    //#[Route("/admin/creer-un-article", name:"create_article", methods:["GET|POST"])]
    /**
     * @Route("/creer-un-article", name="create_article", methods={"GET|POST"})
     */
    public function createArticle(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
       $article = new Article();

       $form = $this->createForm(ArticleFormType::class, $article)
           ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            // Pour accéder à une valeur d'un input de $form, on fait :
                // $form->get('title')->getData()
            
            // Setting des propriétés non mappées     
            $article->setAlias($slugger->slug($article->getTitle()) ); 
            $article->setCreatedAt(new DateTime());
            $article->setUpdatedAt(new Datetime());
            
            // Association d'un auteur à un article, getUser() retourne un objet de type UserInterface
            $article->setAuthor($this->getUser());
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

        return $this->render('admin/form/form_article.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/modifier-un-article/{id}", name="update_article", methods={"GET|POST"}) 
     * L'action est executée 2 fois et accessible par 2 méthods (GET|POST), name= la clé dans show_dashboard
     */
    public function updateArticle(Article $article, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Condition ternaire $article->getPhoto() ?? '' ; = isset($article->getPhoto()) ? $article->getPhoto() : '' ; Si ce n'est pas null
        $originalPhoto = $article->getPhoto() ?? '' ;

        // 1er TOUR en method GET
        $form = $this->createForm(ArticleFormType::class, $article, [
            'photo' => $originalPhoto
        ])->handleRequest($request);

        // 2eme TOUR en method POST
        if($form->isSubmitted() && $form->isValid()) {

            $article->setAlias($slugger->slug($article->getTitle()) );
            $article->setUpdatedAt(new DateTime());

            $file = $form->get('photo')->getData();

            if($file) {
                $extension = '.' . $file->guessExtension();
                //$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $article->getAlias(); 
                $newFilename =  $safeFilename . '_' . uniqid() . $extension; 

                try { 
                    $file->move($this->getParameter('uploads_dir'), $newFilename); 
                    $article->setPhoto($newFilename);

                } catch (FileException $exception) {  
                    // Code a executer si erreur est attrapée
                } // END catch()

            } else {
                $article->setPhoto($originalPhoto);
            } // END if ($file)

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', "L'article ". $article->getTitle() ." a bien été modifié !");

            return $this->redirectToRoute("show_dashboard");
        } // END if ($form)
        
        // Retourne Vue de la method GET
        return $this->render('admin/form/form_article.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }
    
    /**
     * @Route("/archiver-un-article/{id}", name="soft_delete_article", methods={"GET"})
     */
    public function softDeleteArticle(Article $article, EntityManagerInterface $entityManager): Response
    {
        // On set la propriété deletedAt pour archiver l'article, de l'autre coté on affichera les articles dont deletedAt == null
        $article->setDeletedAt(new DateTime());

        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success', "L'article". $article->getTitle() ."a bien été archivé");
        return $this->redirectToRoute("show_dashboard");
    }

    /**
     * @Route("/supprimer-un-article/{id}", name="hard_delete_article", methods={"GET"})
     */
    public function hardDeleteArticle(Article $article, EntityManagerInterface $entityManager): Response
    {
     $entityManager->remove($article);
     $entityManager->flush();


     $this->addFlash('success', "L'article". $article->getTitle() ." a bien été supprimé de la base de données");

     return $this->redirectToRoute("show_dashboard");
    }

    /**
     * @Route("/restaurer-un-article/{id}", name="restore_article", methods={"GET"})
     */
    public function restoreArticle(Article $article, EntityManagerInterface $entityManager): Response
    {
        $article->setDeletedAt();
        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success', "L'article ". $article->getTitle() ." a bien été restauré");

        return $this->redirectToRoute("show_dashboard");
    }
} // END Class
