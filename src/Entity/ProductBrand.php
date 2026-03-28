<?php

namespace App\Entity;

use App\Repository\ProductBrandRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity('slug')]
#[ORM\Entity(repositoryClass: ProductBrandRepository::class)]
class ProductBrand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $slug = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'productBrand', targetEntity: Product::class)]
    private Collection $products;

    /**
     * @var Collection<int, BrandModel>
     */
    #[ORM\OneToMany(mappedBy: 'productBrands', targetEntity: BrandModel::class)]
    private Collection $brandModels;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->brandModels = new ArrayCollection();
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

    public function setName(?string $name): static
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
            $product->setProductBrand($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getProductBrand() === $this) {
                $product->setProductBrand(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BrandModel>
     */
    public function getBrandModels(): Collection
    {
        return $this->brandModels;
    }

    public function addBrandModel(BrandModel $brandModel): static
    {
        if (!$this->brandModels->contains($brandModel)) {
            $this->brandModels->add($brandModel);
            $brandModel->setProductBrands($this);
        }

        return $this;
    }

    public function removeBrandModel(BrandModel $brandModel): static
    {
        if ($this->brandModels->removeElement($brandModel)) {
            // set the owning side to null (unless already changed)
            if ($brandModel->getProductBrands() === $this) {
                $brandModel->setProductBrands(null);
            }
        }

        return $this;
    }
}
