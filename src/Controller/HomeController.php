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
use App\Repository\SizeStockRepository;
use App\Service\CartService;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

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


    #[Route('/api/get-stock', name: 'api_get_stock', methods: ['GET'])]
    public function getStock(Request $request, SizeStockRepository $sizeStockRepository): JsonResponse
    {
        $size = $request->query->get('size');
        $variantId = $request->query->get('variantId');

        // Récupérer le stock pour la variante et la taille spécifiées
        $stock = $sizeStockRepository->findOneBy([
            'size' => $size,
            'productVariant' => $variantId,
        ]);

        if (!$stock) {
            return new JsonResponse(['error' => 'Stock not found'], 404);
        }

        return new JsonResponse(['stock' => $stock->getStock()]);
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
    






    






    


    
    // #[Route('/boutique', name: 'app_shop')]
    // public function shop(
    //     ProductRepository $repoProduct,
    //     WishListService $wishListService,
    //     RewiewsProductRepository $reviewsRepo,
    //     FormFactoryInterface $formFactory,
    //     Request $request
    // ): Response {
    //     // Création et gestion du formulaire de recherche avancée
    //     $search = new SearchProduct();
    //     $form = $formFactory->create(SearchProductType::class, $search, [
    //         'method' => 'GET', // ou 'POST' selon votre cas
    //     ]);
    //     $form->handleRequest($request);
    
    //     // Récupération du terme de recherche depuis la requête
    //     $query = $request->query->get('query', '');
    
    //     // Initialisation des produits
    //     $products = [];
    
    //     // Vérifier si le formulaire a été soumis et est valide
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         // dd($form->isSubmitted(), $form->isValid());exit();
    //         $products = $repoProduct->findByFilters($search);
    //     } else if (!empty($query)) {
    //         // Si un terme de recherche est fourni, effectuer une recherche textuelle
    //         $products = $repoProduct->findBySearchQuery($query);
    //     } else {
    //         // Sinon, charger tous les produits
    //         $products = $repoProduct->findAll();
    //     }

    //     // dd($products);exit();
    
    //     // Compilation des informations supplémentaires pour chaque produit
    //     $productRatings = [];
    //     $isInWishlist = [];
    //     foreach ($products as $product) {
    //         $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
    //         $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
    //     }
    
    //     // Rendu de la vue avec toutes les données nécessaires
    //     return $this->render('pages/home/shop.html.twig', [
    //         'products' => $products,
    //         'search' => $form->createView(),
    //         'productRatings' => $productRatings,
    //         'isInWishlist' => $isInWishlist,
    //     ]);
    // }
    






    #[Route('/boutique', name: 'app_shop')]
    public function shop(
        WishListService $wishListService,
        RewiewsProductRepository $reviewsRepo,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $em,
        Request $request,
        ProductRepository $repoProduct): Response {
        // Création et gestion du formulaire de recherche avancée
        $search = new SearchProduct();
        $form = $this->createForm(SearchProductType::class, $search, [
            'method' => 'GET', // Utilisez GET pour permettre aux utilisateurs de partager des URLs de recherche
        ]);
        $form->handleRequest($request);

        // Récupération du terme de recherche depuis la requête
        $query = $request->query->get('query', '');

        // Initialisation des produits
        $products = [];

        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Filtrer les produits selon les critères spécifiés
            $products = $repoProduct->findByFilters($search, $query);
        } else if (!empty($query)) {
            // Si un terme de recherche est fourni, effectuer une recherche textuelle
            $products = $repoProduct->findBySearchQuery($query);
        } else {
            // Sinon, charger tous les produits
            $products = $repoProduct->findAll();
        }

        // Affichage d'un message si aucun produit ne correspond aux critères
        // if (empty($products)) {
        //     $this->addFlash('error', 'Désolé, aucun produit ne correspond au résultat de votre recherche.');
        // }
        if (empty($products)) {
            $this->addFlash('error', 'Aucun produit trouvé. Voici des suggestions basées sur vos critères.');
            $cleanedQuery = strtolower(str_replace(' ', '', $query));
            $products = $repoProduct->findProductsBySimilar($cleanedQuery);

        
            if (empty($products)) {
                $this->addFlash('error', 'Aucune suggestion trouvée.');
            } else {
                // Si vous avez besoin de convertir les tableaux en objets Product :
                $products = array_map(function ($productData) use ($repoProduct) {
                    return $repoProduct->find($productData['id']); // Recharger les entités par leur ID
                }, $products);
            }
        }

        $productRatings = [];
        $isInWishlist = [];
        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
            $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
        }

        $categoriesForFilter = [];
    $subCategoriesForFilter = [];
    $brandsForFilter = [];
    $brandsModelForFilter = [];

    // On parcourt tous les produits retournés
    foreach ($products as $product) {
        // 🔹 Récupérer la catégorie
        if ($product->getCategorie()) {
            $categoriesForFilter[$product->getCategorie()->getId()] = $product->getCategorie();
        }

        // 🔹 Récupérer la sous-catégorie
        if ($product->getSubCategorie()) {
            $subCat = $product->getSubCategorie();

            // ✅ Forcer Doctrine à charger la sous-catégorie avant de l'ajouter
            if ($em->contains($subCat)) {
                $em->refresh($subCat);
            }

            if (!array_key_exists($subCat->getId(), $subCategoriesForFilter)) {
                $subCategoriesForFilter[$subCat->getId()] = $subCat;
            }
        }

        // 🔹 Récupérer la marque
        if ($product->getProductBrand()) {
            $brand = $product->getProductBrand();
            if (!array_key_exists($brand->getId(), $brandsForFilter)) {
                $brandsForFilter[$brand->getId()] = $brand;
            }
        }

        // 🔹 Récupérer le modèle de la marque
        if ($product->getBrandModel()) {
            $brandModel = $product->getBrandModel();

            // ✅ Forcer Doctrine à charger le modèle avant de l'ajouter
            if ($em->contains($brandModel)) {
                $em->refresh($brandModel);
            }

            if (!array_key_exists($brandModel->getId(), $brandsModelForFilter)) {
                $brandsModelForFilter[$brandModel->getId()] = $brandModel;
            }
        }
    }

        // dump($subCategoriesForFilter);
        // die();


        $form = $formFactory->create(SearchProductType::class, $search, [
            'method'                 => 'GET',
            'filtered_categories'    => $categoriesForFilter,
            'filtered_subCategories' => $subCategoriesForFilter,
            'filtered_brands'        => $brandsForFilter,
            'filtered_brandsModel'   => $brandsModelForFilter,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Filtrer les produits selon les critères spécifiés
            $products = $repoProduct->findByFilters($search, $query);
        } else if (!empty($query)) {
            // Si un terme de recherche est fourni, effectuer une recherche textuelle
            $products = $repoProduct->findBySearchQuery($query);
        } else {
            // Sinon, charger tous les produits
            $products = $repoProduct->findBy([], ['id' => 'DESC']);

        }

        $categoriesForFilter = [];
        $subCategoriesForFilter = [];
        $brandsForFilter     = [];
        $brandsModelForFilter = [];
    
        // On parcourt tous les produits retournés
        foreach ($products as $product) {
            // Récupérer la catégorie
            if ($product->getCategorie()) {
                $cat = $product->getCategorie();
                // Stocker par ID pour éviter doublons
                $categoriesForFilter[$cat->getId()] = $cat;
            }

            if ($product->getSubCategorie()) {
                $subCat = $product->getSubCategorie();
        
                // ✅ Forcer Doctrine à charger la sous-catégorie avant de l'ajouter
                if ($em->contains($subCat)) {
                    $em->refresh($subCat);
                }
        
                if (!array_key_exists($subCat->getId(), $subCategoriesForFilter)) {
                    $subCategoriesForFilter[$subCat->getId()] = $subCat;
                }
            }

            // Récupérer la marque
            if ($product->getProductBrand()) {
                $brandsForFilter[$product->getProductBrand()->getId()] = $product->getProductBrand();
            }

            if ($product->getBrandModel()) {
                $brandsModel = $product->getBrandModel();
        
                // ✅ Forcer Doctrine à charger la sous-catégorie avant de l'ajouter
                if ($em->contains($brandsModel)) {
                    $em->refresh($brandsModel);
                }
        
                if (!array_key_exists($brandsModel->getId(), $brandsModelForFilter)) {
                    $brandsModelForFilter[$brandsModel->getId()] = $brandsModel;
                }
            }
        }
//         dump($subCategoriesForFilter);
// die();

        $form = $formFactory->create(SearchProductType::class, $search, [
            'method'             => 'GET',
            'filtered_categories'=> $categoriesForFilter,
            'filtered_subCategories' => $subCategoriesForFilter,
            'filtered_brands'    => $brandsForFilter,
            'filtered_brandsModel'    => $brandsModelForFilter,
        ]);

        
        // Rendu de la vue avec toutes les données nécessaires
        return $this->render('pages/home/shop.html.twig', [
            'products' => $products,
            'search' => $form->createView(),
            'productRatings' => $productRatings,
             'isInWishlist' => $isInWishlist,
        ]);
    }
     
    

}







































































