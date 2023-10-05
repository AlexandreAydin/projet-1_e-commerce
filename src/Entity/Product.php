<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;


use Vich\UploaderBundle\Mapping\Annotation as Vich;


/**
 * @ORM\Entity
 * @Vich\Uploadable
 */
#[UniqueEntity('slug')]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moreInformations = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(nullable: false)]
    private ?bool $isBestSeller = null;

    #[ORM\Column(nullable: false)]
    private ?bool $isNewArrival = null;

    #[ORM\Column(nullable: false)]
    private ?bool $isFeatured = null;

    #[ORM\Column(nullable: false)]
    private ?bool $isSpacialOffer = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: RelatedProduct::class)]
    private Collection $relatedProducts;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: RewiewsProduct::class, cascade:["remove"])]
    private Collection $rewiewsProducts;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Categorie $categorie = null;

    #[ORM\Column]
    private ?int $quantity = null;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $createdAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $slug = null;

     /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class, cascade: ['persist'],orphanRemoval: true,)]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $cart;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: CartDetails::class)]
    private Collection $cartDetails;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration4 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration5 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration6 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration7 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration8 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText3 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText4 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText5 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText6 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText7 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $illustrationText8 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description2 = null;


        public function __construct()
        {
            $this->updatedAt = new \DateTime();
            $this->createdAt = new \DateTimeImmutable();
            $this->relatedProducts = new ArrayCollection();
            $this->relatedProducts = new ArrayCollection();
            $this->images = new ArrayCollection();
            $this->rewiewsProducts = new ArrayCollection();
            $this->cartDetails = new ArrayCollection();
        }

        public function __toString(): string
    {
        return $this->name;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getMoreInformations(): ?string
    {
        return $this->moreInformations;
    }

    public function setMoreInformations(?string $moreInformations): self
    {
        $this->moreInformations = $moreInformations;

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

    public function isIsBestSeller(): ?bool
    {
        return $this->isBestSeller;
    }

    public function setIsBestSeller(?bool $isBestSeller): self
    {
        $this->isBestSeller = $isBestSeller;

        return $this;
    }

    public function isIsNewArrival(): ?bool
    {
        return $this->isNewArrival;
    }

    public function setIsNewArrival(?bool $isNewArrival): self
    {
        $this->isNewArrival = $isNewArrival;

        return $this;
    }

    public function isIsFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(?bool $isFeatured): self
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function isIsSpacialOffer(): ?bool
    {
        return $this->isSpacialOffer;
    }

    public function setIsSpacialOffer(?bool $isSpacialOffer): self
    {
        $this->isSpacialOffer = $isSpacialOffer;

        return $this;
    }

    

    /**
     * @return Collection<int, RelatedProduct>
     */
    public function getRelatedProducts(): Collection
    {
        return $this->relatedProducts;
    }

    public function addRelatedProduct(RelatedProduct $relatedProduct): self
    {
        if (!$this->relatedProducts->contains($relatedProduct)) {
            $this->relatedProducts->add($relatedProduct);
            $relatedProduct->setProduct($this);
        }

        return $this;
    }

    public function removeRelatedProduct(RelatedProduct $relatedProduct): self
    {
        if ($this->relatedProducts->removeElement($relatedProduct)) {
            // set the owning side to null (unless already changed)
            if ($relatedProduct->getProduct() === $this) {
                $relatedProduct->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RewiewsProduct>
     */
    public function getRewiewsProducts(): Collection
    {
        return $this->rewiewsProducts;
    }

    public function addRewiewsProduct(RewiewsProduct $rewiewsProduct): self
    {
        if (!$this->rewiewsProducts->contains($rewiewsProduct)) {
            $this->rewiewsProducts->add($rewiewsProduct);
            $rewiewsProduct->setProduct($this);
        }

        return $this;
    }

    public function removeRewiewsProduct(RewiewsProduct $rewiewsProduct): self
    {
        if ($this->rewiewsProducts->removeElement($rewiewsProduct)) {
            // set the owning side to null (unless already changed)
            if ($rewiewsProduct->getProduct() === $this) {
                $rewiewsProduct->setProduct(null);
            }
        }

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

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

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
   
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
   
        return $this;
    }

      /**
     * @return Collection<int, ProductImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(ProductImage $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
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

    public function getIllustration1(): ?string
    {
        return $this->illustration1;
    }

    public function setIllustration1(?string $illustration1): self
    {
        $this->illustration1 = $illustration1;

        return $this;
    }

    public function getIllustrationText1(): ?string
    {
        return $this->illustrationText1;
    }

    public function setIllustrationText1(?string $illustrationText1): self
    {
        $this->illustrationText1 = $illustrationText1;

        return $this;
    }

    public function getIllustration2(): ?string
    {
        return $this->illustration2;
    }

    public function setIllustration2(?string $illustration2): self
    {
        $this->illustration2 = $illustration2;

        return $this;
    }

    public function getIllustration3(): ?string
    {
        return $this->illustration3;
    }

    public function setIllustration3(?string $illustration3): self
    {
        $this->illustration3 = $illustration3;

        return $this;
    }

    public function getIllustration4(): ?string
    {
        return $this->illustration4;
    }

    public function setIllustration4(?string $illustration4): self
    {
        $this->illustration4 = $illustration4;

        return $this;
    }

    public function getIllustration5(): ?string
    {
        return $this->illustration5;
    }

    public function setIllustration5(?string $illustration5): self
    {
        $this->illustration5 = $illustration5;

        return $this;
    }

    public function getIllustration6(): ?string
    {
        return $this->illustration6;
    }

    public function setIllustration6(?string $illustration6): self
    {
        $this->illustration6 = $illustration6;

        return $this;
    }

    public function getIllustration7(): ?string
    {
        return $this->illustration7;
    }

    public function setIllustration7(?string $illustration7): self
    {
        $this->illustration7 = $illustration7;

        return $this;
    }

    public function getIllustration8(): ?string
    {
        return $this->illustration8;
    }

    public function setIllustration8(?string $illustration8): self
    {
        $this->illustration8 = $illustration8;

        return $this;
    }

    public function getIllustrationText2(): ?string
    {
        return $this->illustrationText2;
    }

    public function setIllustrationText2(?string $illustrationText2): self
    {
        $this->illustrationText2 = $illustrationText2;

        return $this;
    }

    public function getIllustrationText3(): ?string
    {
        return $this->illustrationText3;
    }

    public function setIllustrationText3(?string $illustrationText3): self
    {
        $this->illustrationText3 = $illustrationText3;

        return $this;
    }

    public function getIllustrationText4(): ?string
    {
        return $this->illustrationText4;
    }

    public function setIllustrationText4(?string $illustrationText4): self
    {
        $this->illustrationText4 = $illustrationText4;

        return $this;
    }

    public function getIllustrationText5(): ?string
    {
        return $this->illustrationText5;
    }

    public function setIllustrationText5(?string $illustrationText5): self
    {
        $this->illustrationText5 = $illustrationText5;

        return $this;
    }

    public function getIllustrationText6(): ?string
    {
        return $this->illustrationText6;
    }

    public function setIllustrationText6(?string $illustrationText6): self
    {
        $this->illustrationText6 = $illustrationText6;

        return $this;
    }

    public function getIllustrationText7(): ?string
    {
        return $this->illustrationText7;
    }

    public function setIllustrationText7(?string $illustrationText7): self
    {
        $this->illustrationText7 = $illustrationText7;

        return $this;
    }

    public function getIllustrationText8(): ?string
    {
        return $this->illustrationText8;
    }

    public function setIllustrationText8(?string $illustrationText8): self
    {
        $this->illustrationText8 = $illustrationText8;

        return $this;
    }

    public function getDescription2(): ?string
    {
        return $this->description2;
    }

    public function setDescription2(?string $description2): self
    {
        $this->description2 = $description2;

        return $this;
    }


}
