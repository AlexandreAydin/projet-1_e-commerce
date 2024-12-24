<?php 

namespace App\Classe;

use App\Entity\User;
use App\Entity\Wishlist;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class WishListService
{
    private $session;
    private $entityManager;
    private $productRepo;
    private $security;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepo,
        Security $security
    ) {
        $this->session = $requestStack->getSession();
        $this->entityManager = $entityManager;
        $this->productRepo = $productRepo;
        $this->security = $security;
    }

    

    public function getWishList()
    {
        return $this->session->get("wishList", []);
    }

    public function updateWishList($wishList)
    { 
        return $this->session->set("wishList", $wishList);
    }

    public function addToWishList($id)
    {
        $user = $this->security->getUser();
    
        if (!$user) {
            throw new \Exception("User must be logged in to add items to wishlist");
        }
    
        // Récupérez la wishlist de l'utilisateur ou créez-en une nouvelle si elle n'existe pas
        $wishList = $this->entityManager->getRepository(Wishlist::class)->findOneBy(['user' => $user]);
        if (!$wishList) {
            $wishList = new Wishlist();
            $wishList->setUser($user);
        }
    
        // Récupérez le produit par son ID
        $product = $this->productRepo->find($id);
        if (!$product) {
            throw new \Exception("Product not found");
        }
    
        // Ajoutez le produit à la liste de souhaits s'il n'y est pas déjà
        if (!$wishList->getProducts()->contains($product)) {
            $wishList->getProducts()->add($product);
        }
    
        $this->entityManager->persist($wishList);
        $this->entityManager->flush();
    }
    

    // public function removeToWishList($id)
    // {
    //     $wishList = $this->getWishList();

    //     if (isset($wishList[$id])) {
    //         unset($wishList[$id]);
    //         $this->updateWishList($wishList);
    //     }
    // }

    public function removeToWishList($id)
{
    $user = $this->security->getUser();
    if (!$user) {
        throw new \Exception("User must be logged in to remove items from the wishlist");
    }

    $wishList = $this->entityManager->getRepository(Wishlist::class)->findOneBy(['user' => $user]);

    $product = $this->productRepo->find($id);
    if (!$product) {
        throw new \Exception("Product not found");
    }

    if ($wishList && $wishList->getProducts()->contains($product)) {
        $wishList->getProducts()->removeElement($product);
        $this->entityManager->persist($wishList);
        $this->entityManager->flush();
    }
}


    public function cleareWishList()
    {
        $this->updateWishList([]);
    }


    public function getWishListDetails()
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception("User must be logged in to view wishlist details");
        }

        $wishList = $this->entityManager->getRepository(Wishlist::class)->findOneBy(['user' => $user]);

        $result = [];
        if ($wishList) {
            foreach ($wishList->getProducts() as $product) {
                $result[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    // 'images' => $product->getImages()->first()->getImageName(),
                    'price' => $product->getPrice(),
                    'quantity' => $product->getQuantity(),
                ];
            }
        }
        
        return $result;
    }


    public function isProductInWishlist($productId): bool
    {
        $user = $this->security->getUser();
        if (!$user) {
            return false; // Si l'utilisateur n'est pas connecté, il ne peut pas avoir de wishlist
        }

        $wishList = $this->entityManager->getRepository(Wishlist::class)->findOneBy(['user' => $user]);

        if (!$wishList) {
            return false; // Pas de wishlist associée à l'utilisateur
        }

        foreach ($wishList->getProducts() as $product) {
            if ($product->getId() === $productId) {
                return true; // Produit trouvé dans la wishlist
            }
        }

        return false; // Produit non trouvé
    }

    public function toggleWishList($productId): bool
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception("Vous devez être connecté pour gérer votre wishlist.");
        }
    
        $wishList = $this->entityManager->getRepository(Wishlist::class)
            ->findOneBy(['user' => $user]);
    
        if (!$wishList) {
            $wishList = new Wishlist();
            $wishList->setUser($user);
        }
    
        $product = $this->productRepo->find($productId);
        if (!$product) {
            throw new \Exception("Produit introuvable.");
        }
    
        // Supprime ou ajoute directement sans boucle
        if ($wishList->getProducts()->contains($product)) {
            $wishList->getProducts()->removeElement($product);
            $action = false; // Produit retiré
        } else {
            $wishList->getProducts()->add($product);
            $action = true; // Produit ajouté
        }
    
        $this->entityManager->persist($wishList);
        $this->entityManager->flush();
    
        return $action;
    }


    
    

}