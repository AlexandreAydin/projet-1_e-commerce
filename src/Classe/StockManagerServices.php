<?php 

namespace App\Classe;

use App\Entity\Order;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockManagerServices{

private $manager;
private $repoProduct;

public function __construct(EntityManagerInterface $manager, ProductRepository $repoProduct)
{
     $this->manager = $manager;
     $this->repoProduct = $repoProduct;
}


//    public function deStock(Order $order){
//         $orderDetails = $order->getOrderDetails()->getValues();

//         foreach ($orderDetails as $key =>$details){
//             $product= $this->repoProduct->findByName($details->getProductName())[0];
//             $newQuantity = $product->getQuantity() - $details->getQuantity();
//             $product->setQuantity($newQuantity);
//             $this->manager->flush();
//         }
//    }


public function deStock(Order $order)
{
    // Récupérer les détails de la commande
    $orderDetails = $order->getOrderDetails()->getValues();

    foreach ($orderDetails as $details) {
        $variant = $details->getVariant(); // Récupérer la variante associée
        $selectedSize = $details->getSelectedSize(); // Récupérer la taille sélectionnée

        if (!$variant) {
            throw new \Exception("Variante non trouvée pour le produit " . $details->getProductName());
        }

        // Trouver le stock associé à la taille sélectionnée
        $sizeStock = $variant->getSizes()->filter(function ($sizeStock) use ($selectedSize) {
            return $sizeStock->getSize() === $selectedSize;
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

    // Sauvegarder les modifications
    $this->manager->flush();
}







}