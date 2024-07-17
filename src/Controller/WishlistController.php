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
    {
        $this->wishListService->addToWishList($id);
        $wishlist = $this->wishListService->getWishListDetails();
        

        return $this->json($wishlist);   
        
    }
    

    #[Route('/mes-favoris/{id}/supprimer', name: 'app_remove_to_wishList')]
    public function remove($id): Response
    {
        $this->wishListService->removeToWishList($id);
        $wishlist = $this->wishListService->getWishListDetails();

        return $this->json($wishlist);  
    }


    #[Route('/mes-favoris/obtenir', name: 'app_get_wishList')]
    public function getWishList($id): Response
    {
        $wishList = $this->wishListService->getWishListDetails();
        return $this->json($wishList);
    }

}