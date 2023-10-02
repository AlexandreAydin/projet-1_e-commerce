<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Order $orders = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?OrderDetails $OrderDetails = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrders(): ?Order
    {
        return $this->orders;
    }

    public function setOrders(?Order $orders): self
    {
        $this->orders = $orders;

        return $this;
    }

    public function getOrderDetails(): ?OrderDetails
    {
        return $this->OrderDetails;
    }

    public function setOrderDetails(?OrderDetails $OrderDetails): self
    {
        $this->OrderDetails = $OrderDetails;

        return $this;
    }
}
