<?php

namespace App\Controller;

use App\Classe\WishListService;
use App\Repository\BrandModelRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProductBrandRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use App\Repository\RewiewsProductRepository;
use App\Repository\SubCategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class CategorieController extends AbstractController
{
    #[Route('/categorie/{slug}', name: 'app_categorie')]
    public function index(
        string $slug,
        CategorieRepository $categorieRepository,
        ProductRepository $repoProduct,
        RewiewsProductRepository $reviewsRepo,
        WishListService $wishListService,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        $category = $categorieRepository->findOneBy(['slug' => $slug]);
    
        if (!$category) {
            throw $this->createNotFoundException("La catégorie demandée n'existe pas.");
        }
    
        $query = $repoProduct->findByCategorySlug($slug);
    
        $paginatedItems = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        $isInWishlist = false;
        $productId = $request->query->get('productId');
        $product = null;


        if ($productId) {
            $product = $repoProduct->find($productId);
    
            if (!$product) {
                throw $this->createNotFoundException("Product not found.");
            }
    
            $isInWishlist = $wishListService->isProductInWishlist($product->getId());
        }

        $isInWishlist = []; // Create a new array to hold the wishlist status for each product.

        $products = $repoProduct->findAllOrderedByIdDesc();

        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
            $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
            $product->getVariants();
        }

    
        return $this->render('pages/categorie/index.html.twig', [
            'category' => $category,
            'items' => $paginatedItems,
            'productRatings' => $productRatings,
            'isInWishlist' => $isInWishlist,
        ]);
    }



    #[Route('/categorie/{categorySlug}/{subCategorySlug}', name: 'app_sub_categorie')]
    public function subCategoryIndex(
        string $categorySlug,
        string $subCategorySlug,
        CategorieRepository $categorieRepository,
        SubCategorieRepository $subCategorieRepository,
        RewiewsProductRepository $reviewsRepo,
        ProductRepository $repoProduct,
        PaginatorInterface $paginator,
        WishListService $wishListService,
        Request $request
    ): Response
    {
        $category = $categorieRepository->findOneBy(['slug' => $categorySlug]);
        if (!$category) {
            throw $this->createNotFoundException("La catégorie demandée n'existe pas.");
        }
    
        // Récupération des sous-catégories basée sur leur appartenance à une catégorie via la relation ManyToMany
        $subCategory = $subCategorieRepository->createQueryBuilder('sc')
            ->innerJoin('sc.categories', 'c')
            ->where('sc.slug = :subCategorySlug')
            ->andWhere('c.id = :categoryId')
            ->setParameter('subCategorySlug', $subCategorySlug)
            ->setParameter('categoryId', $category->getId())
            ->getQuery()
            ->getOneOrNullResult();
    
        if (!$subCategory) {
            throw $this->createNotFoundException("La sous-catégorie demandée n'existe pas.");
        }
    
        $query = $repoProduct->findBy(['subCategorie' => $subCategory]);  // Assurez-vous que votre repoProduct peut gérer cette requête
    
        $paginatedItems = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        $isInWishlist = false;
        $productId = $request->query->get('productId');
        $product = null;


        if ($productId) {
            $product = $repoProduct->find($productId);
    
            if (!$product) {
                throw $this->createNotFoundException("Product not found.");
            }
    
            $isInWishlist = $wishListService->isProductInWishlist($product->getId());
        }

        $isInWishlist = []; // Create a new array to hold the wishlist status for each product.

        $products = $repoProduct->findAllOrderedByIdDesc();

        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
            $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
            $product->getVariants();
        }

    
        return $this->render('pages/categorie/subcategory.html.twig', [
            'category' => $category,
            'subCategory' => $subCategory,
            'isInWishlist' => $isInWishlist,
            'productRatings' => $productRatings,
            'items' => $paginatedItems
        ]);
    }


    #[Route('/marque/{slug}', name: 'app_marque')]
    public function Brandindex(
        string $slug,
        ProductBrandRepository $productBrandRepository,
        ProductRepository $repoProduct,
        RewiewsProductRepository $reviewsRepo,
        WishListService $wishListService,
        PaginatorInterface $paginator,
        Request $request
    ): Response 
    {
        $brand = $productBrandRepository->findOneBy(['slug' => $slug]);

        if (!$brand) {
            throw $this->createNotFoundException("La marque demandée n'existe pas.");
        }

        $query = $repoProduct->findBy(['productBrand' => $brand]); // Assurez-vous que cette méthode existe ou ajustez selon votre implémentation

        $paginatedItems = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        $isInWishlist = [];
        $productId = $request->query->get('productId');
        $product = null;

        if ($productId) {
            $product = $repoProduct->find($productId);

            if (!$product) {
                throw $this->createNotFoundException("Product not found.");
            }

            $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
        }

        $products = $repoProduct->findAllOrderedByIdDesc(); // Assurez-vous que cette méthode existe ou créez-la

        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
            $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
            $product->getVariants();
        }

        return $this->render('pages/brand/index.html.twig', [
            'brand' => $brand,
            'items' => $paginatedItems,
            'productRatings' => $productRatings,
            'isInWishlist' => $isInWishlist
        ]);
    }


    #[Route('/marque/{brandSlug}/{modelSlug}', name: 'app_brand_model')]
    public function brandModelIndex(
        string $brandSlug,
        string $modelSlug,
        ProductBrandRepository $productBrandRepository,
        BrandModelRepository $brandModelRepository,
        ProductRepository $repoProduct,
        RewiewsProductRepository $reviewsRepo,
        WishListService $wishListService,
        PaginatorInterface $paginator,
        Request $request
    ): Response 
    {
        $brand = $productBrandRepository->findOneBy(['slug' => $brandSlug]);
        if (!$brand) {
            throw $this->createNotFoundException("La marque demandée n'existe pas.");
        }

        $model = $brandModelRepository->findOneBy(['slug' => $modelSlug, 'productBrands' => $brand]);
        if (!$model) {
            throw $this->createNotFoundException("Le modèle demandé n'existe pas.");
        }

        $query = $repoProduct->findBy(['brandModel' => $model]);  // Assurez-vous que cette requête est supportée par votre repoProduct

        $paginatedItems = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        $isInWishlist = [];
        $productId = $request->query->get('productId');
        $product = null;

        if ($productId) {
            $product = $repoProduct->find($productId);

            if (!$product) {
                throw $this->createNotFoundException("Product not found.");
            }

            $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
        }

        $products = $repoProduct->findAllOrderedByIdDesc();

        foreach ($products as $product) {
            $productRatings[$product->getId()] = $reviewsRepo->getAverageRatingForProduct($product);
            $isInWishlist[$product->getId()] = $wishListService->isProductInWishlist($product->getId());
            $product->getVariants();
        }

        return $this->render('pages/brand/model_index.html.twig', [
            'brand' => $brand,
            'model' => $model,
            'items' => $paginatedItems,
            'productRatings' => $productRatings,
            'isInWishlist' => $isInWishlist
        ]);
    }



    #[Route('/get-categories-with-subcategories', name: 'get_categories_with_subcategories', methods: ['GET'])]
    public function getCategoriesWithSubcategories(CategorieRepository $categorieRepository): JsonResponse
    {
        $categories = $categorieRepository->findAll();
        $response = [];
    
        foreach ($categories as $category) {
            $subcategories = [];
            foreach ($category->getSubCategories() as $subcategory) {
                $subcategories[] = [
                    'id' => $subcategory->getId(),
                    'name' => $subcategory->getName(),
                ];
            }
    
            $response[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'subcategories' => $subcategories,
            ];
        }
    
        return new JsonResponse($response);
    }
    
    



    // #[Route('/get-subcategories', name: 'get_subcategories', methods: ['POST'])]
    // public function getSubcategories(Request $request, SubCategorieRepository $subCategorieRepository): JsonResponse
    // {
    //     try {
    //         // Décoder les données JSON envoyées par le frontend
    //         $data = json_decode($request->getContent(), true);
    
    //         // Vérifier les données envoyées
    //         if (!isset($data['categories']) || !is_array($data['categories'])) {
    //             return new JsonResponse(['error' => 'Invalid input: categories is missing or invalid'], 400);
    //         }
    
    //         $categoryIds = $data['categories'];
    
    //         // Récupérer les sous-catégories correspondantes
    //         $subcategories = $subCategorieRepository->findSubcategoriesByCategoryIds($categoryIds);
    
    //         return new JsonResponse($subcategories); // Retourne les sous-catégories en JSON
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['error' => $e->getMessage()], 500);
    //     }
    // }
    
    
    
    //   #[Route('/get-subcategories', name: 'get_subcategories', methods: ['POST'])]
    // public function getSubcategories(Request $request, SubCategorieRepository $subCategorieRepository): JsonResponse
    // {
    //     try {
    //         // Décoder les données JSON envoyées par le frontend
    //         $data = json_decode($request->getContent(), true);
    
    //         // Vérifier les données envoyées
    //         if (!isset($data['categories']) || !is_array($data['categories'])) {
    //             return new JsonResponse(['error' => 'Invalid input: categories is missing or invalid'], 400);
    //         }
    
    //         $categoryIds = $data['categories'];
    
    //         // Récupérer les sous-catégories correspondantes
    //         $subcategories = $subCategorieRepository->findSubcategoriesByCategoryIds($categoryIds);
    
    //         return new JsonResponse($subcategories); // Retourne les sous-catégories en JSON
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['error' => $e->getMessage()], 500);
    //     }
    // }

    
    
    #[Route('/get-subcategories', name: 'get_subcategories', methods: ['POST'])]
    public function getSubcategories(Request $request, SubCategorieRepository $subCategorieRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['categories']) || !is_array($data['categories'])) {
                return new JsonResponse(['error' => 'Invalid input'], 400);
            }

            $categoryIds = $data['categories'];
            $subcategories = $subCategorieRepository->findSubcategoriesByCategoryIds($categoryIds);

            return new JsonResponse($subcategories);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }


    #[Route('/get-brandModel', name: 'get_brandModel', methods: ['POST'])]
    public function getBrandModel(Request $request, BrandModelRepository $brandModelRepository): JsonResponse
    {
        try {
            // Récupération et décodage des données JSON
            $data = json_decode($request->getContent(), true);
    
            // Vérification des données entrantes
            if (!isset($data['productBrand']) || !is_array($data['productBrand'])) {
                return new JsonResponse(['error' => 'Invalid input: productBrand is missing or not an array'], 400);
            }
    
            $productBrandIds = $data['productBrand'];
    
            // Appel à la méthode du repository pour récupérer les modèles associés
            $brandModels = $brandModelRepository->findBrandModelByProductBrandIds($productBrandIds);
    
            // Retourne les données au format JSON
            return new JsonResponse($brandModels);
        } catch (\Exception $e) {
            // Gestion des erreurs
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    
    
    

    

    
    
}
