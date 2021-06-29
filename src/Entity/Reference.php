<?php

namespace App\Entity;

use App\Repository\ReferenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ReferenceRepository::class)
 */
class Reference
{
    /**
     * @ORM\Id
     * ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $uniqId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $filename;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $error;

    /**
     * @Assert\File (maxSize = "100m")
     */
    private $file;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $filepath;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUniqId(): ?string
    {
        return $this->uniqId;
    }

    public function setUniqId(string $uniqId): self
    {
        $this->uniqId = $uniqId;

        return $this;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;

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

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null): void
    {
        $this->file = $file;
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

//    protected function getUploadRootDir()
//    {
//        return __DIR__.'/../../public/uploads';
//    }
//
//
//    public function getAbsolutePath()
//    {
//        return null === $this->filename
//            ? null
//            : $this->getUploadRootDir().'/'.$this->uniqId.','.$this->filename;
//    }
//
//    /**
//     * @ORM\PostRemove()
//     */
//    public function removeUpload()
//    {
//        $file = $this->getAbsolutePath();
//        if ($file) {
//            unlink($file);
//        }
//    }
}
