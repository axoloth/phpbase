<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @ORM\Entity(repositoryClass=ArticleRepository::class)
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $description;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="articles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $croppedImageName;

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $croppedImageThumbnailName;

    /**
     * @ORM\OneToMany(targetEntity=ArticlePositionCategory::class, mappedBy="article", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"positionCategory" = "ASC"})
     */
    private $positionCategories;

    public function __construct()
    {
        $this->positionCategories = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
    }

    public function getCategories()
    {
        $categories = [];
        foreach ($this->getPositionCategories() as $positionCategory) {
            $categories[] = $positionCategory->getCategory();
        }

        return $categories;
    }

    public function removeCategory($category)
    {
        foreach ($this->getPositionCategories() as $positionCategory) {
            if ($category === $positionCategory->getCategory()) {
                $this->removePositionCategory($positionCategory);
                $positionCategory->getCategory()->removePositionArticle($positionCategory);

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getImage(): ?File
    {
        return $this->image;
    }

    public function setImage(?File $image): self
    {
        $this->image = $image;

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

    public function getCroppedImageName(): ?string
    {
        return $this->croppedImageName;
    }

    public function getCroppedImageAlt(): ?string
    {
        $alt = substr($this->croppedImageName, strpos($this->croppedImageName, '___') + 3);

        return substr($alt, 0, strpos($alt, '.'));
    }

    public function setCroppedImageName(?string $croppedImageName): self
    {
        $this->croppedImageName = $croppedImageName;

        return $this;
    }

    public function getCroppedImageThumbnailName(): ?string
    {
        return $this->croppedImageThumbnailName;
    }

    public function getCroppedImageThumbnailAlt(): ?string
    {
        $alt = substr($this->croppedImageThumbnailName, strpos($this->croppedImageThumbnailName, '___') + 3);

        return substr($alt, 0, strpos($alt, '.'));
    }

    public function setCroppedImageThumbnailName(?string $croppedImageThumbnailName): self
    {
        $this->croppedImageThumbnailName = $croppedImageThumbnailName;

        return $this;
    }

    /**
     * @return Collection|ArticlePositionCategory[]
     */
    public function getPositionCategories(): Collection
    {
        return $this->positionCategories;
    }

    public function addPositionCategory(ArticlePositionCategory $positionCategory): self
    {
        if (!$this->positionCategories->contains($positionCategory)) {
            $this->positionCategories[] = $positionCategory;
            $positionCategory->setArticle($this);
        }

        return $this;
    }

    public function removePositionCategory(ArticlePositionCategory $positionCategory): self
    {
        if ($this->positionCategories->removeElement($positionCategory)) {
            // set the owning side to null (unless already changed)
            if ($positionCategory->getArticle() === $this) {
                $positionCategory->setArticle(null);
            }
        }

        return $this;
    }
}
