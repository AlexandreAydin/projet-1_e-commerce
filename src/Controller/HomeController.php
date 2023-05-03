<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function single_product(?Product $product):Response {

        if(!$product){
            return $this->redirect('/app_home');
        }

        return $this->render('pages/home/single_product.html.twig',[
            'product' => $product   
        ]);

    }
}
