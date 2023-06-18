<?php 

namespace App\Classe;

use App\Entity\Cart;
use App\Entity\CartDetails;
use App\Entity\Order;
use App\Entity\OrderDetails;
use Doctrine\ORM\EntityManagerInterface;

class OrderServices{

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {   
        $this->manager = $manager;
    }

    public function createOrder($cart)
    {
        $order = new Order();

        $order->setReference($cart->getReference())
                ->setCarrierName($cart->geCarrierName())
                ->setCarrierPrice($cart->getCarrierPrice())
                ->setFullName($cart->getFullName()) 
                ->setDeliveryAddress($cart->getDeliveryAddress())
                ->setMoreInformations($cart->getMoreInformations())
                ->setQuantity($cart->getQuantity())
                ->setSubTotalHT($cart->getSubTotalHT())
                ->setTaxe($cart->getTaxe())
                ->setSubTotalTTC($cart->getSubTotalTTC())
                ->setuser($cart->getUser())
                ->setCreatedAt($cart->getCreatedAt());
        $this->manager->persist($order);

        $products = $cart->getCartDetails()->getValues();
        
        foreach ($products as $cart_product)
        {
            $orderDetails = new OrderDetails();
            $orderDetails->setOrders($order)    
                        ->setProductName($cart_product->getName())
                        ->setProductPrice($cart_product->getPrice()/100)
                        ->setQuantity($cart_product->getQuantity())
                        ->setSubTotalHT($cart_product->getSubTotalHT())
                        ->setTaxe($cart_product->getTaxe())
                        ->setSubTotalTTC($cart_product->getSubTotalTTC());
            $this->manager->persist($orderDetails);
        }

        $this->manager->flush();

        return $order;

    }

    public function saveCart($data, $user)
    {
        $cart = new Cart();
        $reference = $this->generateUuid();
        $address=$data['checkout']['address'];
        $carrier=$data['checkout']['carrier'];
        $informations=$data['checkout']['information'];

        $cart->setReference($reference)
            ->setCarrierName($carrier->getName())
            ->setCarrierPrice($carrier->getPrice()/100)
            ->setFullName($address->getFullName()) 
            ->setDeliveryAddress($address)
            ->setMoreInformations($informations)
            ->setQuantity($data['data']['quantity_cart'])
            ->setSubTotalHT($data['data']['subTotalHT'])
            ->setTaxe($data['data']['Taxe'])
            ->setSubTotalTTC(round(($data['data']['subTotalTTC']+$carrier->getPrice()/100)))
            ->setuser($user)
            ->setCreatedAt(new \DateTimeImmutable());

            $this->manager->persist($cart);

            $cart_details_aray=[];

            foreach ($data['products'] as $products)
            {
                $cartDetails= new CartDetails();

                $subTotal = $products['quantity']* $products['product']->getPrice()/100;

                $cartDetails->setCarts($cart)
                        ->setProductName($products['product']->getName())
                        ->setProductPrice($products['product']->getPrice())
                        ->setQuantity($products['quantity'])
                        ->setSubTotalHT($subTotal)
                        ->setTaxe($subTotal*0.2)
                        ->setSubTotalTTC($subTotal*1.2);
                $this->manager->persist($cartDetails);
                $cart_details_aray[]=$cartDetails;
            }

            $this->manager->flush();
            return $reference;

    }

    public function generateUuid()
    {
        mt_srand((double)microtime()*100000);

        $charid = strtoupper(md5(uniqid(rand(), true)));

        $hyphen = chr(45);

        $uuid= ""
        .substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid, 12, 4).$hyphen
        .substr($charid, 12, 4).$hyphen
        .substr($charid, 16, 4).$hyphen
        .substr($charid, 20, 4);
        return $uuid;
        
    }
}