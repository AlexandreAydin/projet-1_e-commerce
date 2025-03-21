<?php

namespace App\Controller;


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
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Filesystem\Filesystem;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;



class HomeController extends AbstractController
{
    
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $repoProduct, RewiewsProductRepository $reviewsRepo): Response
    {
        // Utiliser la méthode avec tri pour récupérer les produits par ID décroissant
        $products = $repoProduct->findAllOrderedByIdDesc();

        $productBestSeller = $repoProduct->findByIsBestSellerDesc();
        $productNewArrival = $repoProduct->findByIsNewArrivalDesc();
        $productFeatured = $repoProduct->findByIsFeaturedDesc();
        $productSpecialOffer = $repoProduct->findByIsSpacialOfferDesc();

        $reviews = []; // Array to store reviews for all products.
        $totalRating = 0; // Initialize total rating.

        $productRatings = []; // Create a new array to hold the average rating for each product.

        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
        }
        
        foreach ($products as $product) {
            // Adjust this logic based on your actual data model and relationships.
            $productReviews = $product->getRewiewsProducts(); 
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
            'productSpecialOffer'=> $productSpecialOffer,
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
    ): Response 
    {

        if (!$product) { 
            return $this->redirectToRoute('app_home');
        }
        
        // Permet aux clients de noter uniquement le produit qu'ils ont acheté 
        $orders = $orderRepo->findBy([
            'isPaid' => true, 
            'user' => $this->getUser(),
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

            // Gestion des fichiers image
            $imageFiles = [
                'rewiewImage' => $form->get('rewiewImage')->getData(),
                'rewiewImages2' => $form->get('rewiewImages2')->getData(),
                'rewiewImages3' => $form->get('rewiewImages3')->getData(),
                'rewiewImages4' => $form->get('rewiewImages4')->getData(),
                'rewiewImages5' => $form->get('rewiewImages5')->getData(),
                'reviewVideo' => $form->get('reviewVideo')->getData(),
            ];

            foreach ($imageFiles as $property => $file) {
                $setter = 'set' . ucfirst($property);
                if ($file) {
                    $newFilename = uniqid().'.'.$file->guessExtension();
                    try {
                        $file->move(
                            $this->getParameter('images_directory'), // Chemin où stocker les fichiers
                            $newFilename
                        );
                        if ($existingReview) {
                            $existingReview->$setter($newFilename);
                        } else {
                            $newReview->$setter($newFilename);
                        }
                    } catch (FileException $e) {
                        // Gérer l'erreur
                    }
                } else {
                    // Si aucun fichier n'est soumis et qu'une image existe, la supprimer
                    if ($existingReview) {
                        $getter = 'get' . ucfirst($property);
                        $currentImage = $existingReview->$getter();
                        if ($currentImage) {
                            $existingReview->$setter(null); // Supprimer l'image actuelle
                            // Supprimez le fichier du serveur
                            $filesystem = new Filesystem();
                            $filesystem->remove($this->getParameter('images_directory').'/'.$currentImage);
                        }
                    }
                }
            }

            if (!$existingReview) {
                $em->persist($newReview);
            } else {
                $existingReview->setComment($newReview->getComment());
                $existingReview->setNote($newReview->getNote());
                $existingReview->setUpdatedAt(new \DateTimeImmutable());
            }

            $em->flush();
            return $this->redirectToRoute('app_single_product', ['slug' => $product->getSlug()]);
        }

        $totalRating = array_sum(array_map(fn($review) => $review->getNote(), $reviews));
        $averageRating = (count($reviews) > 0) ? $totalRating / count($reviews) : 0;
        $reviewsWithComments = array_filter($reviews, fn($review) => !empty($review->getComment()));
        $reviewCount = count($reviewsWithComments);
        $totalReviews = count($reviews);

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
            'averageRating' => $averageRating, // Afficher la moyenne des étoiles et la note d'un seul utilisateur
            'form' => $form->createView(),
            'reviewCount' => $reviewCount,
            'orders' => $orders,
            'starCounts' => $starCounts, // Afficher le % des personnes qui ont mis combien d'étoiles
            'totalReviews' => $totalReviews,
            'relatedRatings' => $relatedRatings // Afficher les étoiles des autres produits dans details_product
        ]);
    }

    


    #[Route('/boutique', name: 'app_shop')]
    public function shop(ProductRepository $repoProduct, RewiewsProductRepository $reviewsRepo,Request $request): Response
    {
        $products = $repoProduct->findAllOrderedByIdDesc();

        $search = new SearchProduct();
        $form = $this->createForm(SearchProductType::class,$search);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $products= $repoProduct->findWithSearch($search);  
        }

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
