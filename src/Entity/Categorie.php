<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use App\Entity\Coupon;
use App\Entity\SubCategorie;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity('slug')]
#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Product::class)]
    private Collection $products;

    private $coupons;

    /**
     * @var Collection<int, SubCategorie>
     */
    #[ORM\ManyToMany(targetEntity: SubCategorie::class, mappedBy: 'categories')]
    private Collection $subCategories;

    // #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: SubCategorie::class)]
    // private Collection $subCategories;

    public function __toString()
    {
        return $this->getName();
    }


    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->coupons = new ArrayCollection();
        // $this->subCategories = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
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

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategorie($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getCategorie() === $this) {
                $product->setCategorie(null);
            }
        }

        return $this;
    }

    public function addCoupon(Coupon $coupon): self
    {
        if (!$this->coupons->contains($coupon)) {
            $this->coupons->add($coupon);
        }
        return $this;
    }

    public function removeCoupon(Coupon $coupon): self
    {
        $this->coupons->removeElement($coupon);
        return $this;
    }

    // /**
    //  * @return Collection<int, SubCategorie>
    //  */
    // public function getSubCategories(): Collection
    // {
    //     return $this->subCategories;
    // }

    // public function addSubCategory(SubCategorie $subCategory): static
    // {
    //     if (!$this->subCategories->contains($subCategory)) {
    //         $this->subCategories->add($subCategory);
    //         $subCategory->setCategorie($this);
    //     }

    //     return $this;
    // }

    // public function removeSubCategory(SubCategorie $subCategory): static
    // {
    //     if ($this->subCategories->removeElement($subCategory)) {
    //         // set the owning side to null (unless already changed)
    //         if ($subCategory->getCategorie() === $this) {
    //             $subCategory->setCategorie(null);
    //         }
    //     }

    //     return $this;
    // }

    /**
     * @return Collection<int, SubCategorie>
     */
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(SubCategorie $subCategory): static
    {
        if (!$this->subCategories->contains($subCategory)) {
            $this->subCategories->add($subCategory);
            $subCategory->addCategory($this);
        }

        return $this;
    }

    public function removeSubCategory(SubCategorie $subCategory): static
    {
        if ($this->subCategories->removeElement($subCategory)) {
            $subCategory->removeCategory($this);
        }

        return $this;
    }
}
