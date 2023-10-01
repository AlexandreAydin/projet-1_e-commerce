<?php

namespace App\Controller\Cart;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $cartServices;

    public function __construct (CartService $cartServices)
    {
        $this->cartServices = $cartServices;
    }


    // /**
    //  * @Route("/add-to-cart-ajax", name="app_add_to_cart_ajax", methods={"POST"})
    //  */
    // public function addToCartAjax(Request $request, ProductRepository $productRepository): Response
    // {
    //     $productId = $request->request->get('productId');
        
    //     // Ajouter le produit au panier
    //     $this->cartServices->addToCart($productId);

    //     // Récupérer la nouvelle quantité totale d'articles dans le panier
    //     $newCartQuantity = $this->cartServices->getCartQuantity();

    //     // Récupérer les détails du produit ajouté en utilisant le Repository
    //     $product = $productRepository->find($productId);
    //     if (!$product) {
    //         return $this->json(['error' => 'Product not found'], 404);
    //     }

    //     // Récupérer la première image du produit
    //     $firstImage = $product->getImages()[0] ?? null;
    //     $imageName = $firstImage ? $firstImage->getImageName() : null;

    //     // Renvoyer les informations du produit avec la nouvelle quantité du panier
    //     return $this->json([
    //         'newCartQuantity' => $newCartQuantity,
    //         'product' => [
    //             'name' => $product->getName(),
    //             'price' => $product->getPrice(),
    //             'image' => $imageName

    //         ]
    //     ]);
    // }




    #[Route('/panier', name: 'app_cart')]
    public function index(): Response
    {
        $cart= $this->cartServices->getFullCart();
        if(!$cart['products']){
            return $this->redirectToRoute("app_home");
        }
        
    
        return $this->render('pages/cart/index.html.twig', [
            'cart'=>$cart
        ]);
    }


    #[Route('/panier/{id}/ajouter', name: 'app_add_to_cart')]
    public function add($id,): Response
    {
        $this->cartServices->addToCart($id);
        return $this ->redirectToRoute('app_cart');

    }

    #[Route('/mon-panier/{id}/diminuer', name: 'app_delete_to_cart')]
    public function deletFromCart($id): Response
    {
        $this->cartServices->deleteFromCart($id);
    
        return $this ->redirectToRoute('app_cart');
    }

    
    #[Route('/mon-panier/{id}/supprimer', name: 'app_cart_delete')]
    public function cart_delete_all($id): Response
    {
        $this->cartServices->deleteFromCart($id);
        return $this ->redirectToRoute('app_cart');
    }

    #[Route('/mon-panier/{id}/tout-supprimer', name: 'app_cart_delete_all')]
    public function deletAllToCart($id): Response
    {
        $this->cartServices->deleteAllFromCart($id);
        return $this ->redirectToRoute('app_cart');
    }




}