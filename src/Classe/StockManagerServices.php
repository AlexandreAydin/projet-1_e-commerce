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


public function deStock(Order $order){

    $orderDetails = $order->getOrderDetails()->getValues();

    foreach ($orderDetails as $key =>$details){
        $products = $this->repoProduct->findByName($details->getProductName());

        // Vérifiez si le produit a été trouvé
        
        if (!empty($products)) {
            $product = $products[0];
            $newQuantity = $product->getQuantity() - $details->getQuantity();

            
            // Vérifiez si la nouvelle quantité est supérieure ou égale à zéro
            if ($newQuantity >= 0) {
                $product->setQuantity($newQuantity);
            } else {
                // Gérer l'erreur
                throw new \Exception("Stock insuffisant pour le produit " . $product->getName());
            }
        } else {
            // Gérer l'erreur
            throw new \Exception("Produit " . $details->getProductName() . " non trouvé");
        }
    }

    $this->manager->flush();
}







}