<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CommentaryRepository;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * @ORM\Entity(repositoryClass=CommentaryRepository::class)
 */
class Commentary
{
    // Un 'trait' est une sorte de classe PHP qui vous sert à réutiliser des propriétés et des Setters et Getters.(propriétés privés)
    // Cela est utile lorsque vous avez plusieurs entités qui partagent des propriétés communes.

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Pour utiliser ces deux classes PHP, il vous faudra 2 dépendances PHP de Gedmo : composer require gedmo/doctrine-extensions
    // timestamp : c'est une valeur numérique exprimée en secondes qui represente le temps ecoulé (en seconde) depuis le 1er Janv 1970. 00:00
    use TimestampableEntity;
    use SoftDeleteableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, inversedBy="commentaries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $article;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="commentaries")
     */
    private $author;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }
}