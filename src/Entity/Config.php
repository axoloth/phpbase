<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="config", indexes={@ORM\Index(name="config_search_idx", columns={"name"})})
 * @ORM\Entity(repositoryClass=ConfigRepository::class)
 * @UniqueEntity("name")
 */
class Config
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $name;

    /**
     * @ORM\Column(type="json")
     */
    private $value = [];

    public function getType()
    {
        if (isset($this->value['type'])) {
            return $this->value['type'];
        }

        return '';
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function get()
    {
        switch ($this->getType()) {
            case 'integer': return intval($this->value['value']);
            case 'number': return floatval($this->value['value']);
            default: return $this->value['value'];
        }
    }

    public function getRealType()
    {
        return gettype($this->get());
    }

    public function display()
    {
        if (!isset($this->value['type'])) {
            return '';
        }
        if ('json' == $this->value['type']) {
            return json_encode($this->value['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ('integer' == $this->value['type']) {
            return (string) intval($this->value['value']);
        } elseif ('number' == $this->value['type']) {
            return (string) floatval($this->value['value']);
        }

        return $this->value['value'];
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

    public function getValue(): ?array
    {
        return $this->value;
    }

    public function setValue(array $value): self
    {
        $this->value = $value;

        return $this;
    }
}
