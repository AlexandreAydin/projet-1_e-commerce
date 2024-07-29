<?php

namespace App\Entity;

class SearchProduct
{

    /**
     * @var string
     */
    public $string = '';

    private ?int $minPrice = null;

    private ?int $maxPrice = null;
    
    /**
     * Undocumented variable
     *
     * @var Categorie[]
     */
    private array $categories = [];


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

}
