<?php 

namespace App\Classe;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class WishListService
{
    private $session;

    public function __construct(private RequestStack $requestStack, private ProductRepository $productRepo)
    {
        $this->session = $requestStack->getSession();
        $this->productRepo = $productRepo;
    }

    public function getWishList()
    {
        return $this->session->get("wishList", []);
    }

    public function updateWishList($wishList)
    {
        return $this->session->set("wishList", $wishList);
    }

    public function addToWishList ($id,)
    {
        $wishList = $this->getWishList();
        // product exist in whislist
        if(!isset($wishList[$id])){
            $wishList[$id] = 1;
            $this->updateWishList($wishList);
        }
    }

    public function removeToWishList($id)
    {
        $wishList = $this->getWishList();

        if (isset($wishList[$id])) {
            unset($wishList[$id]);
            $this->updateWishList($wishList);
        }
    }

    public function cleareWishList()
    {
        $this->updateWishList([]);
    }

    // public function getWishListDetails()
    // {
    //     $wishList = $this->getWishList();
    //     $result = [];

    //     foreach ($wishList as $id => $quantity) {
    //         $product = $this->productRepo->find($id);
    //         if($product){
    //             $result[] = $product;
    //         }else{
    //             unset($wishList[$id]);
    //             $this->updateWishList($wishList);
    //         }
    //     }
    //     return $result;
    // }   

    public function getWishListDetails()
    {
        $wishList = $this->getWishList();
        $result = [];

        foreach ($wishList as $id => $quantity) {
            $product = $this->productRepo->find($id);
            if($product){
                $result[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'images' => $product->getImages()->first()->getImageName(),
                    'price' => $product->getPrice(),
                    'quantity' => $product->getQuantity(),
                ];
            }else{
                unset($wishList[$id]);
                $this->updateWishList($wishList);
            }
        }
        return $result;
    }


}