<?php

namespace App\Controller;

use App\Classe\WishListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WishlistController extends AbstractController
{
    private $wishListService;

    public function __construct (WishListService $wishListService)
    {
        $this->wishListService = $wishListService;
    }

    #[Route('/mes-favoris', name: 'app_wishList')]
    public function index(): Response
    {
        $wishList = $this->wishListService->getWishListDetails();
    
        return $this->render('pages/wishList/index.html.twig', [
           'wishList' => $wishList
        ]);
    }

    #[Route('/mes-favoris/{id}/ajouter', name: 'app_add_to_wishList')]
    public function add($id): Response
    {
        $this->wishListService->addToWishList($id);
        $wishList = $this->wishListService->getWishListDetails();

        return $this ->redirectToRoute('app_wishList');

        // return $this->json($wishList);
    }
    
    #[Route('/mes-favoris/{id}/supprimer', name: 'app_remove_to_wishList')]
    public function RemoveToWishList($id): Response
    {
        $this->wishListService->removeToWishList($id);
        $wishList = $this->wishListService->getWishListDetails();

        
        return $this ->redirectToRoute('app_wishList');
        
    }

    #[Route('/mes-favoris/get', name: 'app_get_wishList')]
    public function getWishList($id): Response
    {
        $wishList = $this->wishListService->getWishListDetails();
        return $this->json($wishList);
    }

}