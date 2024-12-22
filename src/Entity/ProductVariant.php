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

    #[ORM\OneToMany(mappedBy: 'productVariant', targetEntity: SizeStock::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sizes;

    // #[ORM\Column]
    // private ?int $stock = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    private ?int $offVariant = null;

    #[ORM\OneToMany(mappedBy: 'variantProduct', targetEntity: ProductImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variantImages;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $cart;

    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: CartDetails::class)]
    private Collection $cartDetails;
    

    public function __construct()
    {
        $this->sizes = new ArrayCollection();
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


    public function getSizesValues(): array
    {
        return $this->sizes->map(function (SizeStock $sizeStock) {
            return $sizeStock->getSize();
        })->toArray();
    }
    

    // Getter et setter pour sizes
  /**
     * @return Collection<int, SizeStock>
     */
    public function getSizes(): Collection
    {
        return $this->sizes;
    }

    public function addSize(SizeStock $sizeStock): self
    {
        if (!$this->sizes->contains($sizeStock)) {
            $this->sizes->add($sizeStock);
            $sizeStock->setProductVariant($this); // Set the inverse relationship
        }

        return $this;
    }

    public function removeSize(SizeStock $sizeStock): self
    {
        if ($this->sizes->removeElement($sizeStock)) {
            if ($sizeStock->getProductVariant() === $this) {
                $sizeStock->setProductVariant(null); // Remove the inverse relationship
            }
        }

        return $this;
    }

    public function getStockForSize(string $size): ?string
    {
        foreach ($this->sizes as $sizeEntity) {
            if ($sizeEntity->getSize() === $size) {
                return $sizeEntity->getStock();
            }
        }

        return null; // Retourne null si la taille n'est pas trouvée
    }

    public function getSizeStockDetails(): array
    {
        return $this->sizes->map(function (SizeStock $sizeStock) {
            return [
                'size' => $sizeStock->getSize(),
                'stock' => $sizeStock->getStock()
            ];
        })->toArray();
    }

    

    // public function getStock(): ?int
    // {
    //     return $this->stock;
    // }

    // public function setStock(int $stock): static
    // {
    //     $this->stock = $stock;

    //     return $this;
    // }

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

    /**
     * @return Collection<int, Cart>
     */
    public function getCart(): Collection
    {
        return $this->cart;
    }

    public function addCart(Cart $cart): self
    {
        if (!$this->cart->contains($cart)) {
            $this->cart->add($cart);
            $cart->setProduct($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): self
    {
        if ($this->cart->removeElement($cart)) {
            // set the owning side to null (unless already changed)
            if ($cart->getProduct() === $this) {
                $cart->setProduct(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection<int, CartDetails>
     */
    public function getCartDetails(): Collection
    {
        return $this->cartDetails;
    }

    public function addCartDetail(CartDetails $cartDetail): self
    {
        if (!$this->cartDetails->contains($cartDetail)) {
            $this->cartDetails->add($cartDetail);
            $cartDetail->setProduct($this);
        }

        return $this;
    }

    public function removeCartDetail(CartDetails $cartDetail): self
    {
        if ($this->cartDetails->removeElement($cartDetail)) {
            // set the owning side to null (unless already changed)
            if ($cartDetail->getProduct() === $this) {
                $cartDetail->setProduct(null);
            }
        }

        return $this;
    }
}
