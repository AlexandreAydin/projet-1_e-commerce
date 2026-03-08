<?php

namespace App\Classe;

use App\Entity\Order;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockManagerServices
{
    private $manager;
    private $repoProduct;

    public function __construct(EntityManagerInterface $manager, ProductRepository $repoProduct)
    {
        $this->manager = $manager;
        $this->repoProduct = $repoProduct;
    }

    private function isVirtualSize(string $size): bool
    {
        $upper = strtoupper(trim($size));
        return in_array($upper, ['INDISPONIBLE', 'UNIQUE', 'UNI', 'TAILLE UNIQUE', 'DEFAULT']);
    }

    public function deStock(Order $order)
    {
        $orderDetails = $order->getOrderDetails()->getValues();

        foreach ($orderDetails as $details) {
            $variant = $details->getVariant();
            $selectedSize = strtoupper(trim($details->getSelectedSize() ?? ''));

            if (!$variant) {
                throw new \Exception("Variante non trouvée pour le produit " . $details->getProductName());
            }

            // Taille virtuelle (INDISPONIBLE, UNIQUE...) → pas de stock à décrémenter
            if ($this->isVirtualSize($selectedSize)) {
                continue;
            }

            $sizes = $variant->getSizes();

            if ($sizes->isEmpty()) {
                $currentStock = $variant->getStock();
                if ($currentStock === null || $currentStock < $details->getQuantity()) {
                    throw new \Exception("Stock insuffisant ou non défini pour la variante ID {$variant->getId()}");
                }
                $variant->setStock($currentStock - $details->getQuantity());
            } else {
                $normalizedSelected = str_replace(['TAILLE ', ' '], '', $selectedSize);
                $normalizedSelected = $normalizedSelected === 'UNI' ? 'UNIQUE' : $normalizedSelected;

                $sizeStock = $sizes->filter(function ($sizeStock) use ($normalizedSelected) {
                    $size = str_replace(['TAILLE ', ' '], '', strtoupper($sizeStock->getSize()));
                    $size = $size === 'UNI' ? 'UNIQUE' : $size;
                    return $size === $normalizedSelected;
                })->first();

                if (!$sizeStock) {
                    // Correspondance partielle anti-troncature
                    $sizeStock = $sizes->filter(function ($sizeStock) use ($normalizedSelected) {
                        $size = str_replace(['TAILLE ', ' '], '', strtoupper($sizeStock->getSize()));
                        return str_starts_with($size, $normalizedSelected);
                    })->first();
                }

                if (!$sizeStock) {
                    throw new \Exception("Stock non trouvé pour la taille '{$selectedSize}' de la variante ID {$variant->getId()}.");
                }

                $newStock = $sizeStock->getStock() - $details->getQuantity();

                if ($newStock < 0) {
                    throw new \Exception("Stock insuffisant pour la taille '{$selectedSize}' de la variante ID {$variant->getId()}.");
                }

                $sizeStock->setStock($newStock);
            }

            $this->manager->persist($variant);
        }

        $this->manager->flush();
    }
}