<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class File implements \Serializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $extension;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @var UploadedFile
     */
    private $file;

    /**
     * @var string
     */
    private $filename;

    public function __toString()
    {
        return $this->getFilename();
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreated()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpload()
    {
        if (null === $this->file) {
            return;
        }

        if (null !== $this->name) {
            $this->filename = $this->getPhpPath();
        }
        $this->name = $this->file->getClientOriginalName();
        $this->extension = $this->file->guessExtension();
        if (!$this->extension) {
            $this->extension = 'err';
        }
    }

    /**
     * @ORM\PostPersist
     * @ORM\PostUpdate
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        // Si il y a un ancien fichier
        if (null !== $this->filename) {
            if (file_exists($this->filename)) {
                unlink($this->filename);
            }
        }

        $this->file->move(
            $this->getPhpDir(),
            $this->getFilename()
        );
        $this->file = null;
    }

    /**
     * @ORM\PreRemove
     */
    public function preRemoveUpload()
    {
        // Sauvegarde temporaire du nom de fichier car il dépend de l'id
        $this->filename = $this->getPhpPath();
    }

    /**
     * @ORM\PostRemove
     */
    public function removeUpload()
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function getNumber(): string
    {
        return '_'.str_pad($this->getId(), 8, '0', STR_PAD_LEFT).'__';
    }

    public function getFilename(): ?string
    {
        return $this->getNumber().'_'.$this->getName();
    }

    public function getWebDir()
    {
        return '/upload';
    }

    public function getPhpDir()
    {
        return __DIR__.'/../../public/upload';
    }

    public function getWebPath()
    {
        return $this->getWebDir().'/'.$this->getFilename();
    }

    public function getPhpPath()
    {
        return $this->getPhpDir().'/'.$this->getFilename();
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

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

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

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(UploadedFile $file = null): self
    {
        $this->file = $file;

        return $this;
    }

    public function serialize()
    {
        return serialize([
            $this->id,
            $this->name,
            $this->extension,
        ]);
    }

    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->name,
            $this->extension
        ) = unserialize($serialized);
    }
}