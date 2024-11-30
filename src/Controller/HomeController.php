<?php

namespace App\Controller;

use App\Classe\WishListService;
use App\Entity\OrderDetails;
use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\RewiewsProduct;
use App\Entity\SearchProduct;
use App\Form\RewiewsProductType;
use App\Form\SearchProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
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
    public function index(ProductRepository $repoProduct, RewiewsProductRepository $reviewsRepo, WishListService $wishListService): Response
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

        $isInWishlist = $wishListService->isProductInWishlist($product->getId());

        return $this->render('pages/home/index.html.twig', [
            'controller_name' => 'HomeController',
            'products' => $products, 
            'productBestSeller'=> $productBestSeller,
            'productNewArrival'=> $productNewArrival,
            'productFeatured' => $productFeatured,
            'productSpecialOffer'=> $productSpecialOffer,
            'averageRating' => $averageRating,
            'productRatings' => $productRatings,
            'isInWishlist' => $isInWishlist,
        ]);
    }






    #[Route('/produit/{slug}', name: 'app_single_product')]
    public function singleProduct(
        ?Product $product,
        WishListService $wishListService,
        CartService $cartService,
        RewiewsProductRepository $reviewsRepo,
        EntityManagerInterface $em,
        Request $request,
        ProductRepository $productRepository,
        OrderRepository $orderRepo
    ): Response {
        // Rediriger vers l'accueil si le produit n'existe pas
        if (!$product) {
            return $this->redirectToRoute('app_home');
        }
    
        // Récupérer les variantes du produit et les formater pour le template
        $formattedVariants = array_map(function ($variant) {
            return [
                'id' => $variant->getId(),
                'color' => $variant->getColor(),
                'sizes' => $variant->getSizes()->map(function ($size) {
                    return $size->getSize();
                })->toArray(),
                'price' => $variant->getPrice(),
                'offVariant' => $variant->getOffVariant(),
                'images' => array_map(function ($image) {
                    return ['id' => $image->getId(), 'imageName' => $image->getImageName()];
                }, $variant->getVariantImages()->toArray())
            ];
        }, $product->getVariants()->toArray());
    
        // Permettre aux clients de noter uniquement les produits qu'ils ont achetés
        $orders = $orderRepo->findBy([
            'isPaid' => true,
            'user' => $this->getUser(),
        ]);
    
        // Récupérer les avis et calculer les notes moyennes et le total d'avis
        $reviews = $reviewsRepo->findBy(['product' => $product]);
        $starCounts = array_fill(1, 5, 0);
        foreach ($reviews as $review) {
            $note = $review->getNote();
            $starCounts[$note]++;
        }
    
        // Formulaire pour soumettre un avis
        $newReview = new RewiewsProduct();
        $form = $this->createForm(RewiewsProductType::class, $newReview);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleReviewForm($form, $newReview, $product, $reviewsRepo, $em);
            return $this->redirectToRoute('app_single_product', ['slug' => $product->getSlug()]);
        }
    
        // Calcul des moyennes des notes
        $totalRating = array_sum(array_map(fn($review) => $review->getNote(), $reviews));
        $averageRating = count($reviews) > 0 ? $totalRating / count($reviews) : 0;
        $reviewCount = count(array_filter($reviews, fn($review) => !empty($review->getComment())));
        $totalReviews = count($reviews);
    
        // Récupérer les tailles et couleurs disponibles
        $sizes = [];
        $colors = [];
        foreach ($product->getVariants() as $variant) {
            $variantSizes = array_map(fn($sizeStock) => $sizeStock->getSize(), $variant->getSizes()->toArray());
            $sizes = array_unique(array_merge($sizes, $variantSizes));
        
            if (!in_array($variant->getColor(), $colors)) {
                $colors[] = $variant->getColor();
            }
        }
    
        // Récupérer les produits similaires et leurs notes moyennes
        $relatedRatings = [];
        $categorie = $product->getCategorie();
        
        if ($categorie !== null) {
            foreach ($categorie->getProducts() as $relatedProduct) {
                if ($relatedProduct->getId() !== $product->getId()) {
                    $relatedRatings[$relatedProduct->getId()] = $reviewsRepo->getAverageRatingForProduct($relatedProduct);
                }
            }
        }
        

        $variant = $product->getVariants()->first();

        if (!$variant) {
            throw $this->createNotFoundException('Aucune variante disponible pour ce produit.');
        }

       
        $cart = $cartService->getFullCart(); // Obtenez le panier complet

        $isInWishlist = $wishListService->isProductInWishlist($product->getId());
    
        // Rendu du template
        return $this->render('pages/home/single_product.html.twig', [
            'product' => $product,
            'cart' => $cart, // Transmettez le panier à la vue
            'reviews' => $reviews,
            'variant' => $variant,
            'variants' => $formattedVariants,
            'averageRating' => $averageRating,
            'form' => $form->createView(),
            'reviewCount' => $reviewCount,
            'orders' => $orders,
            'starCounts' => $starCounts,
            'totalReviews' => $totalReviews,
            'sizes' => $sizes,
            'colors' => $colors,
            'relatedRatings' => $relatedRatings,
            'isInWishlist' => $isInWishlist,
        ]);
    }





   
    
    /**
     * Gérer la soumission du formulaire d'avis.
     */
    private function handleReviewForm($form, RewiewsProduct $newReview, Product $product, RewiewsProductRepository $reviewsRepo, EntityManagerInterface $em)
    {
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
                $newFilename = uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('images_directory'), // Chemin de stockage
                        $newFilename
                    );
                    $newReview->$setter($newFilename);
                } catch (FileException $e) {
                    // Gérer l'erreur
                }
            } elseif ($existingReview) {
                $getter = 'get' . ucfirst($property);
                $currentImage = $existingReview->$getter();
                if ($currentImage) {
                    $filesystem = new Filesystem();
                    $filesystem->remove($this->getParameter('images_directory') . '/' . $currentImage);
                    $existingReview->$setter(null);
                }
            }
        }
    
        if ($existingReview) {
            $existingReview->setComment($newReview->getComment());
            $existingReview->setNote($newReview->getNote());
            $existingReview->setUpdatedAt(new \DateTimeImmutable());
        } else {
            $em->persist($newReview);
        }
    
        $em->flush();
    }
    







    






    


    #[Route('/boutique', name: 'app_shop')]
    public function shop(ProductRepository $repoProduct,WishListService $wishListService, RewiewsProductRepository $reviewsRepo,Request $request): Response
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

        
        $isInWishlist = $wishListService->isProductInWishlist($product->getId());
        
        return $this->render('pages/home/shop.html.twig', [
            'products' => $products, 
            'search' => $form->createView(),
            'productRatings' => $productRatings,
            'isInWishlist' => $isInWishlist,
        ]);
    }
}







































































