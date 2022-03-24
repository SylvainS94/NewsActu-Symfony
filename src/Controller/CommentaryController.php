<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentary;
use App\Form\CommentaryFormType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentaryController extends AbstractController
{
    /**
     * @Route("/ajouter-un-commentaire?article_id={id}", name="add_commentary", methods={"GET|POST"})
     */
    public function addCommentary(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentary= new Commentary();

        $form =$this->createForm(CommentaryFormType::class, $commentary)->handleRequest($request);
       
        # Cas où le formulaire n'est pas valide. Lorsque le champ 'comment' est vide, il y la contrainte NotBlank qui 
        if($form->isSubmitted() && $form->isValid() === false ) {
            $this->addFlash('warning', 'Votre commentaire est vide !');

            return $this->redirectToRoute('show_article', [
                'cat_alias' => $article->getCategory()->getAlias(),
                'article_alias' => $article->getAlias(),
                'id' => $article->getId()
            ]);
        } // endif

        if($form->isSubmitted() && $form->isValid()) { // === true
            
            $commentary->setArticle($article);
            $commentary->setCreatedAt(new DateTime());
            $commentary->setupdatedAt(new DateTime());

            $commentary->setAuthor($this->getUser());

            $entityManager->persist($commentary);
            $entityManager->flush();

            $this->addFlash('success', "Vous avez commenté l'article <strong>". $article->getTitle() ." </strong> avec succès !");

            return $this->redirectToRoute('show_article', [
                'cat_alias' => $article->getCategory()->getAlias(),
                'article_alias' => $article->getAlias(),
                'id' => $article->getId()
            ]);
        } // endif

        return $this->render('rendered/form_commentary.html.twig', [
            'form' => $form->createView()
        ]);

    } // End function addCommentary

    //-----------------1ere Façon: --------------------
    // Inconvenients : C'est tres verbeux + les parametres de la route pour faire redirectToRoute peuvent ne pas être accessibles.
    // Avantage : La redirection sera STATIQUE, tous les utilisateurs seront redirigés à l'endroit indiqué. 
    
    //-----------------2eme Façon: --------------------
    // Inconvenients : La redirection se fera en fonction de l'URL de provenance de la requête, à savoir si vous utilisez cette action a plusieurs endroits différents de votre site, 
    // ->l'utilisateur sera redirigé ailleurs que ce que vous avez décidé.
    // Avantage : La redirection devient Dynamique(elle changera en fonction de la provenance de la requête)

    /**
     * @Route("/archiver-mon-commentaire/{id}", name="soft_delete_commentary", methods={"GET"})
     */
    public function softDeleteCommentary(Commentary $commentary, EntityManagerInterface $entityManager, Request $request): Response
    {   
        // Pcke nous allons rediriger vers 'show_article' qui attend 3 arguments, nous avons injecté l'objet Request
        // Cela nous permettra d'acceder aux superglobales PHP ($_GET et $_SERVER => appelés dans l'ordre : query et server)
        // Nous allons voir 2 facons pour rediriger sur la route souhaitée

        $commentary->setDeletedAt(new DateTime());

        // -------------- 1ere Façon : ---------------------
        // dd($request->query->get('article_alias'));
        
        // -------------- 2eme Façon : ---------------------
        // dd($request->server->get('HTTP_REFERER'));

        $entityManager->persist($commentary);
        $entityManager->flush();

        $this->addFlash('success', "Votre commentaire est archivé");

        // -------------- 1ere Façon : --------------------
        // La construction de l'URL a lieu dans le fichier 'show_article.html.twig' sur l'attribut HTML 'href' de la balise <a> .
        //     => Voir 'show_article.html.twig ' pour la suite de la 1ere Facon

        // Nous récupérons les valeurs des parametres passés dans l'url $_GET (query) :
//             return $this->redirectToRoute('show_article', [
//                'cat_alias' => $request->query->get('cat_alias'),
//                'article_alias' => $request->query->get('article_alias'),
//                'id' => $request->query->get('article_id')
//             ]);

        //--------------- 2eme Facon : --------------------
        // Nous avons retiré les paramètres de l'URL,  dans le fichier 'show_article.html.twig'
        //    => Voir 'show_article.html.twig ' pour la suite de la 2ème Façon

        // Ici nous utilisons une clé du $_SERVER (server) qui s'appelle 'HTTP_REFERER'.
        // Cette clé contient l'URL de provenance de la requete ($request)   
        return $this->redirect($request->server->get('HTTP_REFERER'));

    } // End function softDeleteCommentary

} // End class CommentaryController
