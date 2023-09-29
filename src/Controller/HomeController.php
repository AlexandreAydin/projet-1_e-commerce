<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\OrderDetails;
use App\Entity\Product;
use App\Entity\RewiewsProduct;
use App\Entity\SearchProduct;
use App\Form\RewiewsProductType;
use App\Form\SearchProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\RewiewsProductRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $repoProduct, RewiewsProductRepository $reviewsRepo): Response
    {
        $products = $repoProduct->findAll();

        $productBestSeller = $repoProduct->findByIsBestSeller(1);
        $productNewArrival = $repoProduct->findByIsNewArrival(1);
        $productFeatured = $repoProduct->findByIsFeatured(1);
        $productSpecialOffer = $repoProduct->findByIsSpacialOffer(1);

        $reviews = []; // Array to store reviews for all products.
        $totalRating = 0; // Initialize total rating.

        $productRatings = []; // Create a new array to hold the average rating for each product.

        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
        }
        
        foreach ($products as $product) {
            // Adjust this logic based on your actual data model and relationships.
            $productReviews = $product->getrewiewsProducts(); 
            $reviews = array_merge($reviews, $productReviews->toArray());
        
            foreach ($productReviews as $review) {
                $totalRating += $review->getNote(); // Assuming each review has a getNote method that returns the rating.
            }
        }
        
        $averageRating = (count($reviews) > 0) ? $totalRating / count($reviews) : 0;
        

        return $this->render('pages/home/index.html.twig', [
            'controller_name' => 'HomeController',
            'products' => $products, 
            'productBestSeller'=> $productBestSeller,
            'productNewArrival'=> $productNewArrival,
            'productFeatured' => $productFeatured,
            'productSpecialOffer'=>$productSpecialOffer,
            'averageRating' => $averageRating,
            'productRatings' => $productRatings,
        ]);
    }

    #[Route('/produit/{slug}', name: 'app_single_product')]
    public function single_product(
        ?Product $product,
        CartService $cartService,
        RewiewsProductRepository $reviewsRepo,
        EntityManagerInterface $em,
        Request $request,
        OrderRepository $orderRepo
    ): Response {
    
        if (!$product) { 
            return $this->redirectToRoute('app_home');
        }
        
        // $order = $orderRepo->findOneBy(['user' => $user, 'product' => $product]);
        // $orders = $orderRepo->findBy(['isPaid'=>true, 'user'=>$this->getUser()],
        // ['id'=>'DESC']);
        // permet aux clients de noter uniquement le produit qu'il a acheté 
        $currentProductName = $product->getName(); 
        $orders = $orderRepo->findBy([
            'isPaid' => true, 
            'user' => $this->getUser(),
            'productName' => $currentProductName,
        ]);
            
        $reviews = $reviewsRepo->findBy(['product' => $product]);
        $starCounts = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];
        
        
        foreach ($reviews as $review) {
            $note = $review->getNote();  
            if (isset($starCounts[$note])) {
                $starCounts[$note]++;
            }
        }
        $newReview = new RewiewsProduct();
        $form = $this->createForm(RewiewsProductType::class, $newReview);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $newReview->setUser($this->getUser())
                    ->setProduct($product)
                    ->setCreatedAt(new \DateTimeImmutable());
    
            $existingReview = $reviewsRepo->findOneBy([
                'user' => $this->getUser(),
                'product' => $product
            ]);
    
            if (!$existingReview) {
                $em->persist($newReview);
            } else {
                $existingReview->setComment($newReview->getComment());
                $existingReview->setNote($newReview->getNote());
            }
    
            $em->flush();
            return $this->redirectToRoute('app_single_product', ['slug' => $product->getSlug()]);
        }
    
        $totalRating = array_sum(array_map(fn($review) => $review->getNote(), $reviews));
        $averageRating = (count($reviews) > 0) ? $totalRating / count($reviews) : 0;
        $reviewsWithComments = array_filter($reviews, fn($review) => !empty($review->getComment()));
        $reviewCount = count($reviewsWithComments);
        $totalReviews = count($reviews);

        $averageRating = $reviewsRepo->getAverageRatingForProduct($product);

        $relatedRatings = [];
        foreach ($product->getCategorie()->getProducts() as $relatedProduct) {
            if ($relatedProduct->getId() != $product->getId()) {
                $relatedRatings[$relatedProduct->getId()] = $reviewsRepo->getAverageRatingForProduct($relatedProduct);
            }
        }

    
        return $this->render('pages/home/single_product.html.twig', [
            'product' => $product,   
            'cart' => $cartService->getFullCart(),
            'reviews' => $reviews,
            'averageRating' => $averageRating,// permet d'affiché la moyen des étoiles et la note de d'un seul utilisateur
            'form' => $form->createView(),
            'reviewCount' => $reviewCount,
            'orders' => $orders,
            'starCounts' => $starCounts,//permet de d'affiché le % des personnes qui ont mis combien d'étoile
            'totalReviews' => $totalReviews,
            'relatedRatings' => $relatedRatings//permet d'affiché les étoiles des autres produit qui se trouve dans details_product
        ]);
    }
    
    


    #[Route('/boutique', name: 'app_shop')]
    public function shop(ProductRepository $repoProduct, RewiewsProductRepository $reviewsRepo,Request $request): Response
    {
        $products = $repoProduct->findAll();

        $search = new SearchProduct();
        $form = $this->createForm(SearchProductType::class,$search);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
       
            $products= $repoProduct->findWithSearch($search);
            
        }
        
        // $reviews = []; // Array to store reviews for all products.
        // $totalRating = 0; // Initialize total rating.

        // $reviews = []; // Array to store reviews for all products.
        // $totalRating = 0; // Initialize total rating.
        
        // foreach ($products as $product) {
        //     // Adjust this logic based on your actual data model and relationships.
        //     $productReviews = $product->getrewiewsProducts(); 
        //     $reviews = array_merge($reviews, $productReviews->toArray());
        
        //     foreach ($productReviews as $review) {
        //         $totalRating += $review->getNote(); // Assuming each review has a getNote method that returns the rating.
        //     }
        // }
        
        // $averageRating = (count($reviews) > 0) ? $totalRating / count($reviews) : 0;

        //    $totalRating = array_sum(array_map(fn($review) => $review->getNote(), $reviews));
        // $averageRating = (count($reviews) > 0) ? $totalRating / count($reviews) : 0;
        // // $reviewsWithComments = array_filter($reviews, fn($review) => !empty($review->getComment()));
        // // $reviewCount = count($reviewsWithComments);
        // // $totalReviews = count($reviews);

        // $averageRating = $reviewsRepo->getAverageRatingForProduct($product);

        $productRatings = []; // Create a new array to hold the average rating for each product.

        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
        }
    
        
        return $this->render('pages/home/shop.html.twig', [
            'products' => $products, 
            'search' => $form->createView(),
            'productRatings' => $productRatings
        ]);
    }
}
