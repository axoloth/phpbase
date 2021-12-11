<?php

namespace App\Entity;

use App\Repository\ArticlePositionCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArticlePositionCategoryRepository::class)
 */
class ArticlePositionCategory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $positionArticle;

    /**
     * @ORM\Column(type="integer")
     */
    private $positionCategory;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, inversedBy="positionCategories", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $article;

    /**
     * @ORM\ManyToOne(targetEntity=ArticleCategory::class, inversedBy="positionArticles", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    public function __construct()
    {
        $this->positionArticle = 0;
        $this->positionCategory = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPositionArticle(): ?int
    {
        return $this->positionArticle;
    }

    public function setPositionArticle(int $positionArticle): self
    {
        $this->positionArticle = $positionArticle;

        return $this;
    }

    public function getPositionCategory(): ?int
    {
        return $this->positionCategory;
    }

    public function setPositionCategory(int $positionCategory): self
    {
        $this->positionCategory = $positionCategory;

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

    public function getCategory(): ?ArticleCategory
    {
        return $this->category;
    }

    public function setCategory(?ArticleCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
