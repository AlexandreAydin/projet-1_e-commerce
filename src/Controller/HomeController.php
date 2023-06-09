<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\Product;
use App\Entity\SearchProduct;
use App\Form\SearchProductType;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $repoProduct): Response
    {
        $products = $repoProduct->findAll();

        $productBestSeller = $repoProduct->findByIsBestSeller(1);
        $productNewArrival = $repoProduct->findByIsNewArrival(1);
        $productFeatured = $repoProduct->findByIsFeatured(1);
        $productSpecialOffer = $repoProduct->findByIsSpacialOffer(1);


        return $this->render('pages/home/index.html.twig', [
            'controller_name' => 'HomeController',
            'products' => $products, 
            'productBestSeller'=> $productBestSeller,
            'productNewArrival'=> $productNewArrival,
            'productFeatured' => $productFeatured,
            'productSpecialOffer'=>$productSpecialOffer,

        ]);
    }

    #[Route('/produit/{slug}', name: 'app_single_product')]
    public function single_product(?Product $product,CartService $cartService):Response {

        if(!$product){
            return $this->redirect('/app_home');
        }

        return $this->render('pages/home/single_product.html.twig',[
            'product' => $product,   
            'cart'=> $cartService->getFullCart()
            
        ]);

    }


    #[Route('/boutique', name: 'app_shop')]
    public function shop(ProductRepository $repoProduct,Request $request): Response
    {
        $products = $repoProduct->findAll();

        $search = new SearchProduct();
        $form = $this->createForm(SearchProductType::class,$search);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
       
            $products= $repoProduct->findWithSearch($search);
            
        }
        

        return $this->render('pages/home/shop.html.twig', [
            'products' => $products, 
            'search' => $form->createView()
        ]);
    }
}
