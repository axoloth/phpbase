<?php

namespace App\Entity;

use App\Repository\ArticleCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @ORM\Entity(repositoryClass=ArticleCategoryRepository::class)
 */
class ArticleCategory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $slug;

    /**
     * @ORM\Column(type="boolean")
     */
    private $displayedMenu;

    /**
     * @ORM\Column(type="boolean")
     */
    private $displayedHome;

    /**
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @ORM\ManyToOne(targetEntity=ArticleCategory::class, inversedBy="subcategories", cascade={"persist"})
     */
    private $parentCategory;

    /**
     * @ORM\OneToMany(targetEntity=ArticleCategory::class, mappedBy="parentCategory", cascade={"persist"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $subcategories;

    /**
     * @ORM\OneToMany(targetEntity=ArticlePositionCategory::class, mappedBy="category", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"positionArticle" = "ASC"})
     */
    private $positionArticles;

    public function __construct()
    {
        $this->position = 0;
        $this->displayedHome = false;
        $this->displayedMenu = false;
        $this->subcategories = new ArrayCollection();
        $this->positionArticles = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getParentCategories(array $parents = [])
    {
        if ($this->getParentCategory()) {
            $parents[] = $this->getParentCategory();

            return $this->getParentCategory()->getParentCategories($parents);
        }

        return $parents;
    }

    public function getDeep()
    {
        return count($this->getParentCategories());
    }

    public function getArticles()
    {
        $articles = [];
        foreach ($this->getPositionArticles() as $positionArticle) {
            $articles[] = $positionArticle->getArticle();
        }

        return $articles;
    }

    public function removeArticle($article)
    {
        foreach ($this->getPositionArticles() as $positionArticle) {
            if ($article === $positionArticle->getArticle()) {
                $this->removePositionArticle($positionArticle);
                $positionArticle->getArticle()->removePositionCategory($positionArticle);

                return true;
            }
        }

        return false;
    }

    public function computeSlug(SluggerInterface $slugger)
    {
        if (!$this->slug || '-' === $this->slug) {
            $this->slug = (string) $slugger->slug((string) $this)->lower();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDisplayedMenu(): ?bool
    {
        return $this->displayedMenu;
    }

    public function setDisplayedMenu(bool $displayedMenu): self
    {
        $this->displayedMenu = $displayedMenu;

        return $this;
    }

    public function getDisplayedHome(): ?bool
    {
        return $this->displayedHome;
    }

    public function setDisplayedHome(bool $displayedHome): self
    {
        $this->displayedHome = $displayedHome;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getParentCategory(): ?self
    {
        return $this->parentCategory;
    }

    public function setParentCategory(?self $parentCategory): self
    {
        $this->parentCategory = $parentCategory;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getSubcategories(): Collection
    {
        return $this->subcategories;
    }

    public function addSubcategory(self $subcategory): self
    {
        if (!$this->subcategories->contains($subcategory)) {
            $this->subcategories[] = $subcategory;
            $subcategory->setParentCategory($this);
        }

        return $this;
    }

    public function removeSubcategory(self $subcategory): self
    {
        if ($this->subcategories->removeElement($subcategory)) {
            // set the owning side to null (unless already changed)
            if ($subcategory->getParentCategory() === $this) {
                $subcategory->setParentCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ArticlePositionCategory[]
     */
    public function getPositionArticles(): Collection
    {
        return $this->positionArticles;
    }

    public function addPositionArticle(ArticlePositionCategory $positionArticle): self
    {
        if (!$this->positionArticles->contains($positionArticle)) {
            $this->positionArticles[] = $positionArticle;
            $positionArticle->setCategory($this);
        }

        return $this;
    }

    public function removePositionArticle(ArticlePositionCategory $positionArticle): self
    {
        if ($this->positionArticles->removeElement($positionArticle)) {
            // set the owning side to null (unless already changed)
            if ($positionArticle->getCategory() === $this) {
                $positionArticle->setCategory(null);
            }
        }

        return $this;
    }
}
