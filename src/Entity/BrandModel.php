<?php

namespace App\Entity;

use App\Repository\BrandModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity('slug')]
#[ORM\Entity(repositoryClass: BrandModelRepository::class)]
class BrandModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $slug = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'brandModel', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\ManyToOne(inversedBy: 'brandModels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductBrand $productBrands = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName() ?? 'Default Name';
    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setBrandModel($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getBrandModel() === $this) {
                $product->setBrandModel(null);
            }
        }

        return $this;
    }

    public function getProductBrands(): ?ProductBrand
    {
        return $this->productBrands;
    }

    public function setProductBrands(?ProductBrand $productBrands): static
    {
        $this->productBrands = $productBrands;

        return $this;
    }
}
