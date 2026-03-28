<?php

namespace App\Entity;

class SearchProduct
{

    private ?int $minPrice = null;

    private ?int $maxPrice = null;
    
    /**
     * Undocumented variable
     *
     * @var Categorie[]
     */
    private array $categories = [];

       /**
     * Undocumented variable
     *
     * @var Categorie[]
     */
    private ?array $subCategories = [];


    /**
     * Undocumented variable
     *
     * @var ProductBrand[]
     */
    private ?array $productBrand = [];
    

     /**
     * Undocumented variable
     *
     * @var BrandModel[]
     */
    private ?array $brandModel = [];

    public function getMinPrice(): ?int
    {
        return $this->minPrice;
    }

    public function setMinPrice(?int $minPrice): self
    {
        $this->minPrice = $minPrice;

        return $this;
    }

    public function getMaxPrice(): ?int
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?int $maxPrice): self
    {
        $this->maxPrice = $maxPrice;

        return $this;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(?array $categories): self
    {
        $this->categories = $categories;

        return $this;
    }


    public function getSubCategories(): ?array
    {
        return $this->subCategories;
    }

    public function setSubCategories(?array $subCategories): self
    {
        $this->subCategories = $subCategories;

        return $this;
    }

    public function getProductBrand(): ?array
    {
        return $this->productBrand;
    }

    public function setProductBrand(?array $productBrand): self
    {
        $this->productBrand = $productBrand;

        return $this;
    }

    public function getBrandModel(): ?array
    {
        return $this->brandModel;
    }

    public function setBrandModel(?array $brandModel): self
    {
        $this->brandModel = $brandModel;

        return $this;
    }

}
