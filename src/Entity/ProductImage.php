<?php

namespace App\Entity;

use App\Repository\ProductImageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;

use Vich\UploaderBundle\Mapping\Annotation as Vich;
#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
#[Vich\Uploadable]
class ProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'products', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?File $imageFile = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?Product $product = null;

    // #[ORM\ManyToOne(targetEntity: ProductVariant::class, inversedBy: 'variantImages')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?ProductVariant $variantProduct = null;

    #[ORM\ManyToOne(inversedBy: 'images')] // Pour les images associées au produit parent
    #[ORM\JoinColumn(nullable: true)] // Autorise null car une image peut être associée uniquement à une variante
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class, inversedBy: 'variantImages')] // Pour les images associées aux variantes
    #[ORM\JoinColumn(nullable: true)] // Autorise null pour les mêmes raisons
    private ?ProductVariant $variantProduct = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $color = null; 


    public function __toString(): string
    {
        return $this->imageName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function setImageSize(?int $imageSize): self
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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


    public function getVariantProduct(): ?ProductVariant
    {
        return $this->variantProduct;
    }

    public function setVariantProduct(?ProductVariant $variantProduct): self
    {
        $this->variantProduct = $variantProduct;

        return $this;
    }


    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }


}
