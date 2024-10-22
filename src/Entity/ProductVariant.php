<?php

namespace App\Entity;

use App\Repository\ProductVariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
class ProductVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'productVariants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'json')]
    private array $sizes = [];

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    private ?int $offVariant = null;

    #[ORM\OneToMany(mappedBy: 'variantProduct', targetEntity: ProductImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variantImages;

    public function __construct()
    {
        $this->sizes = []; 
        $this->variantImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    // Getter et setter pour sizes
    public function getSizes(): array
    {
        return $this->sizes;
    }
    
    public function setSizes(?array $sizes): self
    {
        $this->sizes = $sizes ?? [];
    
        return $this;
    }
    

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getOffVariant(): ?int
    {
        return $this->offVariant;
    }

    public function setOffVariant(?int $offVariant): self
    {
        $this->offVariant = $offVariant;

        return $this;
    }

    public function getVariantImages(): Collection
    {
        return $this->variantImages;
    }

    public function addVariantImage(ProductImage $variantImage): self
    {
        if (!$this->variantImages->contains($variantImage)) {
            $this->variantImages[] = $variantImage;
            $variantImage->setVariantProduct($this);
        }

        return $this;
    }

    public function removeVariantImage(ProductImage $variantImage): self
    {
        if ($this->variantImages->removeElement($variantImage)) {
            if ($variantImage->getVariantProduct() === $this) {
                $variantImage->setVariantProduct(null);
            }
        }

        return $this;
    }
}
