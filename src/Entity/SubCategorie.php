<?php

namespace App\Entity;

use App\Entity\Categorie;
use App\Repository\SubCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity('slug')]
#[ORM\Entity(repositoryClass: SubCategorieRepository::class)]
class SubCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $slug = null;

    // #[ORM\ManyToOne(inversedBy: 'subCategories')]
    // private ?Categorie $categorie = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'subCategorie', targetEntity: Product::class)]
    private Collection $products;

    /**
     * @var Collection<int, Categorie>
     */
    #[ORM\ManyToMany(targetEntity: Categorie::class, inversedBy: 'subCategories')]
    #[ORM\JoinTable(name: 'subcategories_categories')]
    private Collection $categories;


    // #[ORM\ManyToMany(targetEntity: \App\Entity\Categorie::class, inversedBy: 'subCategories')]
    // #[ORM\JoinTable(name: 'sub_categorie_categorie')]
    // private Collection $categories;


    public function __toString()
    {
        return $this->getName();
    }

    public function __construct()
    {
        // $this->categories = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->categories = new ArrayCollection();
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

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    // public function getCategorie(): ?Categorie
    // {
    //     return $this->categorie;
    // }

    // public function setCategorie(?Categorie $categorie): static
    // {
    //     $this->categorie = $categorie;

    //     return $this;
    // }


    // /**
    //  * @return Collection<int, \App\Entity\Annonce\Immobilier\Categorie>
    //  */
    // public function getCategories(): Collection
    // {
    //     return $this->categories;
    // }

    // public function addCategory(\App\Entity\Categorie $category): static
    // {
    //     if (!$this->categories->contains($category)) {
    //         $this->categories->add($category);
    //         $category->setSubCategorie($this);
    //     }

    //     return $this;
    // }

    // public function removeCategory(\App\Entity\Categorie $category): static
    // {
    //     if ($this->categories->removeElement($category)) {
    //         // set the owning side to null (unless already changed)
    //         if ($category->getSubCategorie() === $this) {
    //             $category->setSubCategorie(null);
    //         }
    //     }

    //     return $this;
    // }

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
            $product->setSubCategorie($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getSubCategorie() === $this) {
                $product->setSubCategorie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Categorie>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Categorie $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Categorie $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }


}
