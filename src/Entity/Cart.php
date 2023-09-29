<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\Table(name: '`Cart`')]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255)]
    private ?string $carrierName = null;

    #[ORM\Column]
    private ?float $carrierPrice = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $deliveryAddress = null;

    #[ORM\Column]
    private ?bool $isPaid = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moreInformations = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'Carts', targetEntity: CartDetails::class, cascade: ['remove'])]
    private Collection $CartDetails;

    #[ORM\ManyToOne(inversedBy: 'Carts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'Carts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?float $subTotalHT = null;

    #[ORM\Column]
    private ?float $taxe = null;

    #[ORM\Column]
    private ?float $subTotalTTC = null;

    public function __construct()
    {
        $this->CartDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getCarrierName(): ?string
    {
        return $this->carrierName;
    }

    public function setCarrierName(string $carrierName): self
    {
        $this->carrierName = $carrierName;

        return $this;
    }

    public function getCarrierPrice(): ?float
    {
        return $this->carrierPrice*100;
    }

    public function setCarrierPrice(float $carrierPrice): self
    {
        $this->carrierPrice = $carrierPrice;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function isIsPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): self
    {
        $this->isPaid = $isPaid;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, CartDetails>
     */
    public function getCartDetails(): Collection
    {
        return $this->CartDetails;
    }

    public function addCartDetail(CartDetails $CartDetail): self
    {
        if (!$this->CartDetails->contains($CartDetail)) {
            $this->CartDetails->add($CartDetail);
            $CartDetail->setCarts($this);
        }

        return $this;
    }

    public function removeCartDetail(CartDetails $CartDetail): self
    {
        if ($this->CartDetails->removeElement($CartDetail)) {
            // set the owning side to null (unless already changed)
            if ($CartDetail->getCarts() === $this) {
                $CartDetail->setCarts(null);
            }
        }

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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getSubTotalHT(): ?float
    {
        return $this->subTotalHT*100;
    }

    public function setSubTotalHT(float $subTotalHT): self
    {
        $this->subTotalHT = $subTotalHT;

        return $this;
    }

    public function getTaxe(): ?float
    {
        return $this->taxe*100;
    }

    public function setTaxe(float $Taxe): self
    {
        $this->taxe = $Taxe;

        return $this;
    }

    public function getSubTotalTTC(): ?float
    {
        return $this->subTotalTTC*100;
    }

    public function setSubTotalTTC(float $subTotalTTC): self
    {
        $this->subTotalTTC = $subTotalTTC;

        return $this;
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

}
