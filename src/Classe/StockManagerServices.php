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

    public function deStock(Order $order)
    {
        // Récupérer les détails de la commande
        $orderDetails = $order->getOrderDetails()->getValues();

        foreach ($orderDetails as $details) {
            $variant = $details->getVariant(); // Récupérer la variante associée
            $selectedSize = strtoupper(trim($details->getSelectedSize())); // Normaliser la taille sélectionnée

            if (!$variant) {
                throw new \Exception("Variante non trouvée pour le produit " . $details->getProductName());
            }

            // Normaliser la taille sélectionnée : convertir TAILLE UNI en TAILLE UNIQUE
            $normalizedSelected = str_replace(['TAILLE ', ' '], '', $selectedSize);
            $normalizedSelected = $normalizedSelected === 'UNI' ? 'UNIQUE' : $normalizedSelected;

            // Récupérer les tailles disponibles
            $sizes = $variant->getSizes();

            // Cas spécial : produit sans tailles (stock global)
            if ($sizes->isEmpty()) {
                $currentStock = $variant->getStock(); // Supposons un champ stock dans ProductVariant
                if ($currentStock === null || $currentStock < $details->getQuantity()) {
                    throw new \Exception("Stock insuffisant ou non défini pour la variante ID {$variant->getId()}");
                }
                $variant->setStock($currentStock - $details->getQuantity());
            } else {
                // Trouver le stock associé à la taille normalisée
                $sizeStock = $sizes->filter(function ($sizeStock) use ($normalizedSelected) {
                    $size = str_replace(['TAILLE ', ' '], '', strtoupper($sizeStock->getSize()));
                    $size = $size === 'UNI' ? 'UNIQUE' : $size;
                    return $size === $normalizedSelected;
                })->first();

                if (!$sizeStock) {
                    throw new \Exception("Stock non trouvé pour la taille '{$selectedSize}' de la variante ID {$variant->getId()}.");
                }

                // Réduire le stock pour cette taille
                $newStock = $sizeStock->getStock() - $details->getQuantity();

                if ($newStock < 0) {
                    throw new \Exception("Stock insuffisant pour la taille '{$selectedSize}' de la variante ID {$variant->getId()}.");
                }

                $sizeStock->setStock($newStock); // Mettre à jour le stock
            }

            $this->manager->persist($variant);
        }

        // Sauvegarder les modifications
        $this->manager->flush();
    }
}