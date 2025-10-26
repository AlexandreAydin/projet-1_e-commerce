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

        // $isInWishlist = $wishListService->isProductInWishlist($product->getId());

        return $this->render('pages/home/index.html.twig', [
            'controller_name' => 'HomeController',
            'products' => $products, 
            'productBestSeller'=> $productBestSeller,
            'productNewArrival'=> $productNewArrival,
            'productFeatured' => $productFeatured,
            'productSpecialOffer'=> $productSpecialOffer,
            'averageRating' => $averageRating,
            'productRatings' => $productRatings,
            // 'isInWishlist' => $isInWishlist,
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
            $newReview->setUser($this->getUser())
                    ->setProduct($product)
                    ->setCreatedAt(new \DateTimeImmutable());

            $existingReview = $reviewsRepo->findOneBy([
                'user' => $this->getUser(),
                'product' => $product
            ]);
        
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

        // // $isInWishlist = $wishListService->isProductInWishlist($product->getId());


        
    
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
            // 'isInWishlist' => // $isInWishlist,
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
    


#[Route('/boutique', name: 'app_shop', methods: ['GET'])]
public function shop(
    WishListService $wishListService,
    RewiewsProductRepository $reviewsRepo,
    FormFactoryInterface $formFactory,
    EntityManagerInterface $em,
    Request $request,
    ProductRepository $repoProduct
): Response {
    // --- 1) Lire le terme de recherche (query OU q) ---
    $rawQuery = (string) $request->query->get('query', '');
    if ($rawQuery === '') {
        $rawQuery = (string) $request->query->get('q', '');
    }

    // --- 2) Correcteur métier + normalisation légère ---
    $normalize = static function (string $s): string {
        $s = mb_strtolower($s);
        if (class_exists(\Transliterator::class)) {
            if ($tr = \Transliterator::create('Any-Latin; Latin-ASCII')) {
                $s = $tr->transliterate($s);
            }
        }
        return preg_replace('/[^a-z0-9]+/u', '', $s) ?? '';
    };

    // Remplacements "substring" sur la version normalisée (couvre plastation5 -> playstation5)
    $applyTypoCorrections = static function (string $q) use ($normalize): string {
        $qNorm = $normalize($q);
        $map = [
            'palstation'  => 'playstation',
            'plaistation' => 'playstation',
            'plastation'  => 'playstation',
            'playstion'   => 'playstation',
            'playstaton'  => 'playstation',
        ];
        foreach ($map as $needle => $replacement) {
            if (strpos($qNorm, $needle) !== false) {
                $qNorm = str_replace($needle, $replacement, $qNorm);
            }
        }
        // si playstation + chiffre collés → ajoute un espace pour l'affichage/LIKE brut
        if (preg_match('/^(playstation)(\d+)$/', $qNorm, $m)) {
            return $m[1] . ' ' . $m[2]; // "playstation 5"
        }
        // cas oralb → "oral b" pour l'affichage/LIKE brut (le repo gère aussi la version compactée)
        if ($qNorm === 'oralb') {
            return 'oral b';
        }
        return $q; // sinon on garde tel quel
    };

    $query = $rawQuery !== '' ? $applyTypoCorrections($rawQuery) : '';

    // --- 3) Construire le formulaire une première fois (sans filtres dynamiques) ---
    $search = new SearchProduct();
    $form = $this->createForm(SearchProductType::class, $search, ['method' => 'GET']);
    $form->handleRequest($request);

    // --- 4) Récupération des produits (1er passage) ---
    if ($form->isSubmitted() && $form->isValid()) {
        // Filtres avancés si soumis (on transmet la requête corrigée)
        $products = $repoProduct->findByFilters($search, $query);
    } elseif ($query !== '') {
        // Recherche textuelle si terme présent (requête corrigée)
        $products = $repoProduct->findBySearchQuery($query);
    } else {
        // Pas de recherche → derniers produits
        $products = $repoProduct->findBy([], ['id' => 'DESC']);
    }

    // --- 5) Fallback "similar" si rien trouvé et qu'on a une requête ---
    if (empty($products) && $query !== '') {
        $this->addFlash('error', 'Aucun produit trouvé. Voici des suggestions basées sur votre recherche.');
        // IMPORTANT : passer la chaîne corrigée "humaine" (ex: "playstation 5", "oral b")
        $suggestions = $repoProduct->findProductsBySimilar($query);

        if (!empty($suggestions)) {
            // Si le repo renvoie des arrays (id, ...) -> recharger les entités
            if (is_array($suggestions[0] ?? null) && array_key_exists('id', $suggestions[0])) {
                $products = array_values(array_filter(array_map(
                    fn($row) => $repoProduct->find($row['id'] ?? null),
                    $suggestions
                )));
            } else {
                $products = $suggestions; // déjà des entités
            }
        } else {
            $this->addFlash('error', 'Aucune suggestion trouvée.');
        }
    }

    // --- 6) Construire les listes pour filtres dynamiques à partir des produits courants ---
    $categoriesForFilter    = [];
    $subCategoriesForFilter = [];
    $brandsForFilter        = [];
    $brandsModelForFilter   = [];

    foreach ($products as $product) {
        if (!$product) { continue; }

        if ($cat = $product->getCategorie()) {
            $categoriesForFilter[$cat->getId()] = $cat;
        }
        if ($sub = $product->getSubCategorie()) {
            if ($em->contains($sub)) { $em->refresh($sub); }
            $subCategoriesForFilter[$sub->getId()] = $sub;
        }
        if ($brand = $product->getProductBrand()) {
            $brandsForFilter[$brand->getId()] = $brand;
        }
        if ($model = $product->getBrandModel()) {
            if ($em->contains($model)) { $em->refresh($model); }
            $brandsModelForFilter[$model->getId()] = $model;
        }
    }

    // --- 7) Recréer le formulaire avec options dynamiques, puis ré-appliquer les filtres si soumis ---
    $form = $formFactory->create(SearchProductType::class, $search, [
        'method'                 => 'GET',
        'filtered_categories'    => $categoriesForFilter,
        'filtered_subCategories' => $subCategoriesForFilter,
        'filtered_brands'        => $brandsForFilter,
        'filtered_brandsModel'   => $brandsModelForFilter,
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // On réapplique les filtres sur la base de la même requête corrigée
        $products = $repoProduct->findByFilters($search, $query);
    }

    // --- 8) Notes / avis (sur la liste finale) ---
    $productRatings = [];
    foreach ($products as $p) {
        if ($p) {
            $productRatings[$p->getId()] = $reviewsRepo->getAverageRatingForProduct($p);
        }
    }

    // --- 9) Rendu ---
    return $this->render('pages/home/shop.html.twig', [
        'products'       => $products,
        'search'         => $form->createView(),
        'productRatings' => $productRatings,
        // 'isInWishlist' => ...
    ]);
}

     
    

}







































































