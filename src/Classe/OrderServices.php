<?php 

namespace App\Classe;

use App\Entity\Cart;
use App\Entity\CartDetails;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\ProductVariant;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderServices{

    private $manager;
    private $repoProduct;
    private $repoProductVariant;

    public function __construct(EntityManagerInterface $manager, ProductRepository $repoProduct, ProductVariantRepository $repoProductVariant)
    {   
        $this->manager = $manager;
        $this->repoProductVariant = $repoProductVariant;
        $this->repoProduct = $repoProduct;
    }

    private function convertToArray($collection): array
    {
        if ($collection instanceof \Doctrine\Common\Collections\Collection) {
            return $collection->toArray();
        }
        if (is_array($collection)) {
            return $collection;
        }
        return []; // Retourne un tableau vide si ce n'est ni une collection ni un tableau
    }

    public function createOrder(Cart $cart)
    {
        $order = new Order();

        $order->setReference($cart->getReference())
            ->setCarrierName($cart->getCarrierName())
            ->setCarrierPrice($cart->getCarrierPrice() / 100)
            ->setFullName($cart->getFullName())
            ->setProductName($cart->getProductName())
            ->setDeliveryAddress($cart->getDeliveryAddress())
            ->setBillingAddress($cart->getBillingAddress())
            ->setMoreInformations($cart->getMoreInformations())
            ->setQuantity($cart->getQuantity())
            ->setSubTotalHT($cart->getSubTotalHT() / 100)
            ->setTaxe($cart->getTaxe() / 100)
            ->setSubTotalTTC($cart->getSubTotalHT() + $cart->getTaxe() + $cart->getCarrierPrice())
            ->setUser($cart->getUser())
            ->setCreatedAt($cart->getCreatedAt());
        $this->manager->persist($order);

        foreach ($cart->getCartDetails() as $cartProduct) {
            $orderDetails = new OrderDetails();
            $variant = $cartProduct->getVariant();

            if (!$variant) {
                throw new \Exception("La variante est introuvable pour le produit '{$cartProduct->getProductName()}'.");
            }

            $sizes = $this->convertToArray($variant->getSizes());
            $selectedSize = $cartProduct->getSelectedSize();

            if (!$selectedSize || !in_array($selectedSize, $sizes)) {
                throw new \Exception("La taille sélectionnée '{$selectedSize}' n'est pas valide pour la variante ID {$variant->getId()}.");
            }

            $orderDetails->setOrders($order)
                ->setProductName($cartProduct->getProductName())
                ->setProductPrice($cartProduct->getProductPrice() / 100)
                ->setVariant($variant)
                ->setQuantity($cartProduct->getQuantity())
                ->setSubTotalHT($cartProduct->getSubTotalHT() / 100)
                ->setTaxe($cartProduct->getTaxe() / 100)
                ->setSubTotalTTC($cartProduct->getSubTotalTTC())
                ->setSelectedSize($selectedSize);

            $this->manager->persist($orderDetails);
        }

        $this->manager->flush();

        return $order;
    }

    public function getLineItems($cart)
    {
        $cartDetails = $cart->getCartDetails();
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';
        
        $line_items = [];
        foreach ($cartDetails as $details) {
            $product = $this->repoProduct->findOneByName($details->getProductName());
            $variant = $details->getVariant(); // Récupération de la variante associée
            
            // Ajouter les informations de la variante, si elle existe
            $variantDetails = $variant ? [
                'id' => $variant->getId(),
                'price' => $variant->getPrice(),
                'offVariant' => $variant->getOffVariant(),
                'size' => $variant->getSizes(), // Attention, vérifiez que 'sizes' est bien défini
                'color' => $variant->getColor(),
            ] : null;
    
            $line_items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $variant ? $variant->getPrice() * 100 : $product->getPrice(), // Priorité au prix de la variante
                    'product_data' => [
                        'name' => $product->getName(),
                        // 'images' => [$YOUR_DOMAIN . "/uploads/products/" . $product->getImages()[0]->getImageName()],
                    ],
                ],
                'quantity' => $details->getQuantity(),
                'variant' => $variantDetails, // Ajout des détails de la variante
            ];
        }
    
        // Ajouter les frais de livraison
        $line_items[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $cart->getCarrierPrice(),
                'product_data' => [
                    'name' => 'Carrier ( ' . $cart->getCarrierName() . ' )',
                    'images' => [$YOUR_DOMAIN . "/uploads/products/"],
                ],
            ],
            'quantity' => 1,
        ];
    
        // Ajouter la TVA
        $line_items[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $cart->getTaxe(),
                'product_data' => [
                    'name' => 'TVA(20%)',
                    'images' => [$YOUR_DOMAIN . "/uploads/products/"],
                ],
            ],
            'quantity' => 1,
        ];
    
        return $line_items;
    }
    

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
            $cartDetails = new CartDetails();
    
            $variant = $this->repoProductVariant->find($productData['variant']['id']);
            if (!$variant) {
                throw new \Exception("Variant with ID {$productData['variant']['id']} not found.");
            }

            $productEntity = $this->repoProduct->find($productData['product']['id']);
            if (!$productEntity) {
                throw new \Exception('Product not found.');
            }

            $selectedSize = $productData['variant']['size'] ?? null;
            $sizes = $variant->getSizes();

            // Convertir la collection Doctrine en un tableau PHP
            if ($sizes instanceof \Doctrine\Common\Collections\Collection) {
                $sizes = $sizes->toArray();
            }

            if (!$selectedSize || !in_array($selectedSize, $sizes)) {
                throw new \Exception("La taille sélectionnée '{$selectedSize}' n'est pas valide pour la variante ID {$variant->getId()}.");
            }
            $subTotal = $productData['quantity'] * $productEntity->getPrice() / 100;

            // $cartDetails->setVariant($variant);
    
            $productEntity = $this->repoProduct->find($productData['product']['id']); // Assuming $productData['product'] is an array and has 'id'
            if (!$productEntity) {
                throw new \Exception('Product not found.');
            }

            // dump($productData['product']['id'], $productData['variant']['id']);exit();
            
            $cart->setProduct($productEntity);
            $cart->setProductName($productEntity->getName());

            $subTotal = $productData['quantity'] * $productEntity->getPrice() / 100;

            // $variant = $this->manager->getRepository(ProductVariant::class)->find($productData['variant']['id']);

            // if (!$variant) {
            //     throw new \Exception("Variant with ID {$productData['variant']['id']} not found.");
            // }

            // $cartDetails->setVariant($variant);

            $variant = $this->repoProductVariant->find($productData['variant']['id']); // Récupère la variante
            if (!$variant) {
                throw new \Exception("Variant with ID {$productData['variant']['id']} not found.");
            }


            $cartDetails->setCarts($cart)
                ->setProductName($productEntity->getName())
                ->setProductPrice($productEntity->getPrice())
                ->setProduct($productEntity)
                ->setQuantity($productData['quantity'])
                ->setSubTotalHT($subTotal)
                ->setTaxe($subTotal/1.2 * 0.2)
                ->setVariant($variant)
                ->setSubTotalTTC($subTotal * 1.2)
                ->setVariant($variant) // Associer la variante
                ->setSelectedSize($selectedSize);

                
            
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

