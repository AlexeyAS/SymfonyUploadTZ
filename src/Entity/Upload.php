<?php

namespace App\Entity;

use App\Repository\UploadRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UploadRepository::class)
 */
class Upload
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $hash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $error;

    /**
     * @ORM\ManyToOne(targetEntity=Reference::class)
     * @ORM\JoinColumn(name="filepath", referencedColumnName="filepath", nullable=true, onDelete="SET NULL")
     * @ORM\Column(type="string", nullable=true)
     */
    private $filepath;

//    /**
//     * @Assert\File (maxSize = "1000m")
//     */
//    private $file;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getFilepath()
    {
        return $this->filepath;
    }

    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;

        return $this;
    }
}
