<?php 

namespace App\Classe;

use App\Entity\Cart;
use App\Entity\CartDetails;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderServices{

    private $manager;
    private $repoProduct;

    public function __construct(EntityManagerInterface $manager, ProductRepository $repoProduct)
    {   
        $this->manager = $manager;
        $this->repoProduct = $repoProduct;
    }

    public function createOrder($cart)
    {
        $order = new Order();

        $order->setReference($cart->getReference())
                ->setCarrierName($cart->getCarrierName())
                ->setCarrierPrice($cart->getCarrierPrice()/100)
                ->setFullName($cart->getFullName()) 
                ->setProductName($cart->getProductName())
                ->setDeliveryAddress($cart->getDeliveryAddress())
                ->setBillingAddress($cart->getBillingAddress())
                ->setMoreInformations($cart->getMoreInformations())
                ->setQuantity($cart->getQuantity())
                ->setSubTotalHT($cart->getSubTotalHT()/100)
                ->setTaxe($cart->getTaxe()/100)
                ->setSubTotalTTC($cart->getSubTotalHT() + $cart->getTaxe() + $cart->getCarrierPrice())
                ->setUser($cart->getUser())
                ->setCreatedAt($cart->getCreatedAt());
        $this->manager->persist($order);

        $products = $cart->getCartDetails()->getValues();
        
        foreach ($products as $cart_product)
        {
            $orderDetails = new OrderDetails();
            $orderDetails->setOrders($order)    
                        ->setProductName($cart_product->getProductName())
                        ->setProductPrice($cart_product->getProductPrice()/100)
                        ->setQuantity($cart_product->getQuantity())
                        ->setSubTotalHT($cart_product->getSubTotalHT()*100)
                        ->setTaxe($cart_product->getTaxe()*100)
                        ->setSubTotalTTC($cart_product->getSubTotalTTC());
            $this->manager->persist($orderDetails);
        }

        $this->manager->flush();

        return $order;

    }

    public function getLineItems($cart){
        $cartDetails = $cart->getCartDetails();
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';
        
        $line_items = [];
        foreach ($cartDetails as $details){
            $product = $this->repoProduct->findOneByName($details->getProductName());
            $line_items[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $product->getPrice(),
                        'product_data' => [
                            'name' => $product->getName(),
                            'images' => [$YOUR_DOMAIN. "/uploads/products/".$product->getImages()[0]->getImageName()],
                        ],
                    ],
                    'quantity' => $details->getQuantity(),
                ];
        }



            //carrier
            $line_items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $cart->getCarrierPrice(),
                    'product_data' => [
                        'name' => 'Carrier ( '.$cart->getCarrierName().' )',
                        'images' => [$YOUR_DOMAIN. "/uploads/products/"],
                    ],
                ],
                'quantity' => 1,
            ];

        //Taxe
            $line_items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $cart->getTaxe(),
                    'product_data' => [
                        'name' => 'TVA(20%)',
                        'images' => [$YOUR_DOMAIN. "/uploads/products/"],
                    ],
                ],
                'quantity' => 1,
            ];

            return $line_items;
    }  

    // public function saveCart($data, $user)
    // {
    //     $cart = new Cart();
    //     $reference = $this->generateUuid();
    //     $address=$data['checkout']['address'];
    //     $carrier=$data['checkout']['carrier'];
    //     $informations=$data['checkout']['information'];
    //     foreach ($data['products'] as $products){
    //     $cart->setReference($reference)
    //         ->setCarrierName($carrier->getName())
    //         ->setCarrierPrice($carrier->getPrice()/100)
    //         ->setFullName($address->getFullName()) 
    //         ->setDeliveryAddress($address)
    //         ->setMoreInformations($informations)
    //         ->setQuantity($data['data']['cart_count'])
    //         ->setSubTotalHT($data['data']['subTotalHT'])
    //         ->setTaxe($data['data']['Taxe'])
    //         ->setSubTotalTTC(($data['data']['subTotalHT']+($data['data']['Taxe'])))
    //         ->setUser($user)
    //         ->setProduct($products['product'])
    //         ->setProductName($products['product'])
    //         ->setCreatedAt(new \DateTimeImmutable());
    //     }
    //         $this->manager->persist($cart);

    //         $cart_details_aray=[];

    //         foreach ($data['products'] as $products)
    //         {
    //             $cartDetails= new CartDetails();

    //             $subTotal = $products['quantity']* $products['product']->getPrice()/100;

    //             $cartDetails->setCarts($cart)
    //                     ->setProductName($products['product']->getName())
    //                     ->setProductPrice($products['product']->getPrice())
    //                     ->setProduct($products['product'])
    //                     ->setQuantity($products['quantity'])
    //                     ->setSubTotalHT($subTotal)
    //                     ->setTaxe($subTotal*0.2)
    //                     ->setSubTotalTTC($subTotal*1.2);
    //             $this->manager->persist($cartDetails);
    //             $cart_details_aray[]=$cartDetails;
    //         }

    //         $this->manager->flush();
    //         return $reference;

    // }

    public function saveCart($data, $user)
{
    $reference = $this->generateUuid();
    $address = $data['checkout']['address'];
    $billingAddress = $data['checkout']['billingAddress'];
    $carrier = $data['checkout']['carrier'];
    $informations = $data['checkout']['information'];
    
    $cart = new Cart();
    $cart->setReference($reference)
        ->setCarrierName($carrier->getName())
        ->setCarrierPrice($carrier->getPrice() / 100)
        ->setFullName($address->getFullName()) 
        ->setDeliveryAddress($address)
        ->setBillingAddress($billingAddress)
        ->setMoreInformations($informations)
        ->setQuantity($data['data']['cart_count'])
        ->setSubTotalHT($data['data']['subTotalHT'])
        ->setTaxe($data['data']['Taxe'])
        ->setSubTotalTTC($data['data']['subTotalHT'] + $data['data']['Taxe'])
        ->setUser($user)
        ->setCreatedAt(new \DateTimeImmutable());
    
    // Adding each product to cart details
    foreach ($data['products'] as $productData) {
        $productEntity = $this->repoProduct->find($productData['product']['id']); // Assuming $productData['product'] is an array and has 'id'
        if (!$productEntity) {
            throw new \Exception('Product not found.');
        }
        
        $cart->setProduct($productEntity);
        $cart->setProductName($productEntity->getName());

        $cartDetails = new CartDetails();
        $subTotal = $productData['quantity'] * $productEntity->getPrice() / 100;

        $cartDetails->setCarts($cart)
            ->setProductName($productEntity->getName())
            ->setProductPrice($productEntity->getPrice())
            ->setProduct($productEntity)
            ->setQuantity($productData['quantity'])
            ->setSubTotalHT($subTotal)
            ->setTaxe($subTotal/1.2 * 0.2)
            ->setSubTotalTTC($subTotal * 1.2);
        
        $this->manager->persist($cartDetails);
    }
    
    $this->manager->persist($cart);
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

