<?php

namespace App\Entity;

use App\Repository\CartDetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartDetailsRepository::class)]
class CartDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column]
    private ?float $productPrice = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?float $subTotalHt = null;

    #[ORM\Column]
    private ?float $taxe = null;

    #[ORM\Column]
    private ?float $subTotalTTC = null;

    #[ORM\ManyToOne(inversedBy: 'CartDetails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $Carts = null;

    #[ORM\ManyToOne(inversedBy: 'cartDetails')]
    private ?Product $product = null;

    // #[ORM\Column(length: 255)]
    // private ?string $productId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;

        return $this;
    }

    public function getProductPrice(): ?float
    {
        return $this->productPrice;
    }

    public function setProductPrice(float $productPrice): self
    {
        $this->productPrice = $productPrice;

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

    public function getSubTotalHt(): ?float
    {
        return $this->subTotalHt;
    }

    public function setSubTotalHt(float $subTotalHt): self
    {
        $this->subTotalHt = $subTotalHt;

        return $this;
    }

    public function getTaxe(): ?float
    {
        return $this->taxe;
    }

    public function setTaxe(float $taxe): self
    {
        $this->taxe = $taxe;

        return $this;
    }

    public function getSubTotalTTC(): ?float
    {
        return $this->subTotalTTC;
    }

    public function setSubTotalTTC(float $subTotalTTC): self
    {
        $this->subTotalTTC = $subTotalTTC;

        return $this;
    }

    public function getCarts(): ?Cart
    {
        return $this->Carts;
    }

    public function setCarts(?Cart $Carts): self
    {
        $this->Carts = $Carts;

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

    // public function getProductId(): ?string
    // {
    //     return $this->productId;
    // }

    // public function setProductId(string $productId): self
    // {
    //     $this->productId = $productId;

    //     return $this;
    // }
}
