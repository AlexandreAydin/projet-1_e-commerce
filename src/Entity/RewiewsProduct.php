<?php

namespace App\Entity;

use App\Repository\RewiewsProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RewiewsProductRepository::class)]
class RewiewsProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable:true)]
    private ?int $note = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'rewiewsProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'rewiewsProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;


    #[ORM\Column(type: 'string', nullable:true, length: 255)]
    private ?string $rewiewImage = null;

    #[ORM\Column(type: 'string', nullable:true, length: 255)]
    private ?string $rewiewImages2 = null;

    #[ORM\Column(type: 'string', nullable:true, length: 255)]
    private ?string $rewiewImages3 = null;

    #[ORM\Column(type: 'string', nullable:true, length: 255)]
    private ?string $rewiewImages4 = null;

    #[ORM\Column(type: 'string', nullable:true, length: 255)]
    private ?string $rewiewImages5 = null;

    #[ORM\Column(type: 'string', nullable:true, length: 255)]
    private ?string $reviewVideo = null;


    public function __construct()
{
    $this->createdAt = new \DateTimeImmutable();
    $this->updatedAt = new \DateTimeImmutable();
}


    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }
    
    public function setNote(?int $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRewiewImage(): ?string
    {
        return $this->rewiewImage;
    }

    public function setRewiewImage(?string $rewiewImage): self
    {
        $this->rewiewImage = $rewiewImage;
        return $this;
    }

    public function getRewiewImages2(): ?string
    {
        return $this->rewiewImages2;
    }

    public function setRewiewImages2(?string $rewiewImages2): self
    {
        $this->rewiewImages2 = $rewiewImages2;
        return $this;
    }

    public function getRewiewImages3(): ?string
    {
        return $this->rewiewImages3;
    }

    public function setRewiewImages3(?string $rewiewImages3): self
    {
        $this->rewiewImages3 = $rewiewImages3;
        return $this;
    }

    public function getRewiewImages4(): ?string
    {
        return $this->rewiewImages4;
    }

    public function setRewiewImages4(?string $rewiewImages4): self
    {
        $this->rewiewImages4 = $rewiewImages4;
        return $this;
    }

    public function getRewiewImages5(): ?string
    {
        return $this->rewiewImages5;
    }

    public function setRewiewImages5(?string $rewiewImages5): self
    {
        $this->rewiewImages5 = $rewiewImages5;
        return $this;
    }

    public function getReviewVideo(): ?string
    {
        return $this->reviewVideo;
    }

    public function setReviewVideo(?string $reviewVideo): self
    {
        $this->reviewVideo = $reviewVideo;

        return $this;
    }

}
