<?php

namespace App\Controller;

use App\Classe\WishListService;
use App\Entity\Product;
use App\Entity\Wishlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WishlistController extends AbstractController
{
    private $wishListService;
    private $entityManager;

    public function __construct (WishListService $wishListService, EntityManagerInterface $entityManager)
    {
        $this->wishListService = $wishListService;
        $this->entityManager = $entityManager;
    }

    #[Route('/mes-favoris', name: 'app_wishList')]
    public function showWishlist(): Response
    // {
    //     $user = $this->getUser();
    //     if (!$user) {
    //         throw new \Exception("User must be logged in to view wishlist");
    //     }
    //     $wishList = $user->getWishlist();
    //     $products = $wishList->getProducts();
        
    //     return $this->render('pages/wishList/index.html.twig', [
    //         'wishList' => $products
    //     ]);
    // }
    {

        $wishlist = $this->wishListService->getWishListDetails();
        $wishlist_json = json_encode($wishlist);

        return $this->render('pages/wishList/index.html.twig', [
            'controller_name' => 'WishListController',
            'wishList' => $wishlist,
            "wishlist_json"=>$wishlist_json
        ]);
    }


    #[Route('/mes-favoris/{id}/ajouter', name: 'app_add_to_wishList')]
    public function add(string $id, Product $product): Response
    // {
    //     $user = $this->getUser();
    //     if (!$user) {
    //         throw new \Exception("User must be logged in to view wishlist");
    //     }
    //     $wishList = $user->getWishlist();
    
    //     // If the user doesn't have a wishlist yet, create one
    //     if (!$wishList) {
    //         $wishList = new Wishlist();
    //         $wishList->setUser($user);
    //         $user->setWishlist($wishList);
    //     }

    //     // Add product to wishlist
    //     $wishList->addProduct($product);
    
    //     // Persist changes
    //     $this->entityManager->persist($wishList);
    //     $this->entityManager->flush();
    
    //     return $this->redirectToRoute('app_wishList');
    // }
    {
        $this->wishListService->addToWishList($id);
        $wishlist = $this->wishListService->getWishListDetails();
        
        // return $this->redirectToRoute("app_wishList");

        return $this->json($wishlist);   
        
    }
    

    #[Route('/mes-favoris/{id}/supprimer', name: 'app_remove_to_wishList')]
    public function remove($id): Response
    // {
    //     $user = $this->getUser();

    //     if (!$user) {
    //         throw new \Exception("User must be logged in to modify the wishlist");
    //     }

    //     $wishList = $user->getWishlist();

    //     if (!$wishList) {
    //         // If there's no wishlist, there's nothing to remove from. 
    //         // You can decide if you want to throw an exception, return a message, etc.
    //         return $this->redirectToRoute('app_wishList'); 
    //     }

    //     // Remove product from wishlist
    //     $wishList->removeProduct($product);

    //     // Persist changes
    //     $this->entityManager->persist($wishList);
    //     $this->entityManager->flush();

    //     return $this->redirectToRoute('app_wishList');
    // }
    {
        $this->wishListService->removeToWishList($id);
        $wishlist = $this->wishListService->getWishListDetails();

        // return $this->redirectToRoute("app_wish_list");

        return $this->json($wishlist);  
    }


    #[Route('/mes-favoris/obtenir', name: 'app_get_wishList')]
    public function getWishList($id): Response
    {
        $wishList = $this->wishListService->getWishListDetails();
        return $this->json($wishList);
    }

}