<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\SearchProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
    * @return Product[] Returns an array of Product objects
    */
    public function findWithSearch($search)
    {
        $query = $this->createQueryBuilder('p')
            ->leftJoin('p.variants', 'v'); // Join with ProductVariant entity
    
        // Filtrer sur le prix de base du produit
        if ($search->getMinPrice()) {
            $query = $query->andWhere('p.price >= :minPrice')
                           ->setParameter('minPrice', $search->getMinPrice()*100);
        }
    
        if ($search->getMaxPrice()) {
            $query = $query->andWhere('p.price <= :maxPrice')
                           ->setParameter('maxPrice', $search->getMaxPrice()*100);
        }
    
        // Filtrer sur le prix des variantes, en tenant compte des réductions
        if ($search->getMinPrice()) {
            $query = $query->orWhere(
                '(v.price * (1 - COALESCE(v.offVariant, 0) / 100) >= :minVariantPrice)')
                ->setParameter('minVariantPrice', $search->getMinPrice()*100);
        }
    
        if ($search->getMaxPrice()) {
            $query = $query->orWhere(
                '(v.price * (1 - COALESCE(v.offVariant, 0) / 100) <= :maxVariantPrice)')
                ->setParameter('maxVariantPrice', $search->getMaxPrice()*100);
        }

        if (!empty($search->categories)) {
            $query = $query->andWhere('p.categorie IN (:categories)')
                           ->setParameter('categories', $search->categories);
        }

        // dump($search);exit();
    
        // Filtre sur les chaînes de recherche
        // if (!empty($search->string)) {
        //     $query = $query
        //         ->andWhere('p.name LIKE :string')
        //         ->setParameter('string', "%{$search->string}%");
        // }
    
        return $query->getQuery()->getResult();
    }
    


   public function findAllOrderedByIdDesc()
   {
       return $this->createQueryBuilder('p')
           ->orderBy('p.id', 'DESC')
           ->getQuery()
           ->getResult();
   }

   public function findByIsBestSellerDesc()
   {
       return $this->createQueryBuilder('p')
           ->where('p.isBestSeller = :bestSeller')
           ->setParameter('bestSeller', 1)
           ->orderBy('p.id', 'DESC')
           ->getQuery()
           ->getResult();
   }

   public function findByIsNewArrivalDesc()
   {
       return $this->createQueryBuilder('p')
           ->where('p.isNewArrival = :newArrival')
           ->setParameter('newArrival', 1)
           ->orderBy('p.id', 'DESC')
           ->getQuery()
           ->getResult();
   }

   public function findByIsFeaturedDesc()
   {
       return $this->createQueryBuilder('p')
           ->where('p.isFeatured = :featured')
           ->setParameter('featured', 1)
           ->orderBy('p.id', 'DESC')
           ->getQuery()
           ->getResult();
   }

   public function findByIsSpacialOfferDesc()
   {
       return $this->createQueryBuilder('p')
           ->where('p.isSpacialOffer = :specialOffer')
           ->setParameter('specialOffer', 1)
           ->orderBy('p.id', 'DESC')
           ->getQuery()
           ->getResult();
   }

   public function findByCategorySlug($slug)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.categorie', 'c')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery();
    }

   public function paginationQuery()
   {
       return $this->createQueryBuilder('a')
           ->orderBy('a.id', 'ASC')
           ->getQuery()
       ;
   }











    // public function findBySearchQuery(string $query, ?array $categories = null): array 
    // {
    //     $qb = $this->createQueryBuilder('p')
    //             ->where('
    //                 p.name LIKE :query OR
    //                 p.description LIKE :query OR
    //                 p.ean LIKE :query  ');

    //     if ($categories) {
    //         $qb->andWhere('p.categorie IN (:categories)')
    //         ->setParameter('categories', $categories);
    //     }

    //     $qb->setParameter('query', '%' . $query . '%');

    //     return $qb->getQuery()->getResult();
    // }



    public function findBySearchQuery(string $query, ?array $categories = null): array 
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.productBrand', 'pb') // Joindre l'entité ProductBrand
            ->leftJoin('p.brandModel', 'bm')   // Joindre l'entité BrandModel
            ->where('
                p.name LIKE :query OR
                p.description LIKE :query OR
                p.ean LIKE :query OR
                pb.name LIKE :query OR
                bm.name LIKE :query
            ')
            ->setParameter('query', '%' . $query . '%');
    
        if ($categories) {
            $qb->andWhere('p.categorie IN (:categories)')
               ->setParameter('categories', $categories);
        }
    
        return $qb->getQuery()->getResult();
    }
    





    // public function findBySearchQuery(string $query): array {
    //     return $this->createQueryBuilder('p')
    //         ->where('p.name LIKE :query OR p.description LIKE :query')
    //         ->setParameter('query', '%' . $query . '%')
    //         ->getQuery()
    //         ->getResult();
    // }

    

    
    
    









    // public function findByFilters(SearchProduct $search): array {
    //     $qb = $this->createQueryBuilder('p')
    //                ->leftJoin('p.variants', 'v')
    //                ->groupBy('p.id');  // Grouper les résultats pour éviter les doublons.
    
    //     // Utiliser andX() pour combiner les conditions de manière logique
    //     $priceConditions = $qb->expr()->andX();
    
    //     // Ajout des conditions pour le prix minimum des variantes
    //     if ($search->getMinPrice() !== null) {
    //         $priceConditions->add($qb->expr()->gte(
    //             'v.price * (1 - COALESCE(v.offVariant, 0) / 100)', ':minPrice'  // Calcul du prix des variantes après réduction
    //         ));
    //         $qb->setParameter('minPrice', $search->getMinPrice());
    //     }
    
    //     // Ajout des conditions pour le prix maximum des variantes
    //     if ($search->getMaxPrice() !== null) {
    //         $priceConditions->add($qb->expr()->lte(
    //             'v.price * (1 - COALESCE(v.offVariant, 0) / 100)', ':maxPrice'  // Calcul du prix des variantes après réduction
    //         ));
    //         $qb->setParameter('maxPrice', $search->getMaxPrice());
    //     }
    
    //     // Ajouter les conditions de prix à la requête si nécessaire
    //     if ($priceConditions->count() > 0) {
    //         $qb->andWhere($priceConditions);
    //     }
    
    //     // Filtrage par catégories si spécifié
    //     if (!empty($search->getCategories())) {
    //         $qb->andWhere('p.categorie IN (:categories)')
    //            ->setParameter('categories', $search->getCategories());
    //     }
        
    
    //     // Exécution de la requête pour obtenir les résultats
    //     $result = $qb->getQuery()->getResult();
       
    //     dd($result);exit();
    //     return $result;  // Retourner les résultats filtrés
    // }
    
    
    
    public function findByFilters(SearchProduct $search, string $query = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.variants', 'v')
            ->groupBy('p.id')
            ->orderBy('p.name', 'ASC');
    
        if ($query) {
            $qb->andWhere($qb->expr()->orX(
                'p.name LIKE :query',
                'p.description LIKE :query'
            ))->setParameter('query', '%' . $query . '%');
        }
    
        if (!empty($search->getCategories())) {
            $qb->andWhere('p.categorie IN (:categories)')
               ->setParameter('categories', $search->getCategories());
        }
    
        if (!empty($search->getSubCategories())) {
            $qb->andWhere('p.subCategorie IN (:subCategories)')
               ->setParameter('subCategories', $search->getSubCategories());
        }

        if (!empty($search->getProductBrand())) {
            $qb->andWhere('p.productBrand IN (:productBrand)')
               ->setParameter('productBrand', $search->getProductBrand());
        }

        
        if (!empty($search->getBrandModel())) {
            $qb->andWhere('p.brandModel IN (:brandModel)')
               ->setParameter('brandModel', $search->getBrandModel());
        }

        // dd($search->getBrandModel());exit();

        // dd([
        //     'categories' => $search->getCategories(),
        //     'subCategories' => $search->getSubCategories()
        // ]);
    
        if ($search->getMinPrice() !== null) {
            $qb->andWhere($qb->expr()->orX(
                'p.price >= :minPrice',
                'v.price * (1 - COALESCE(v.offVariant, 0) / 100) >= :minPrice'
            ))->setParameter('minPrice', $search->getMinPrice());
        }
    
        if ($search->getMaxPrice() !== null) {
            $qb->andWhere($qb->expr()->orX(
                'p.price <= :maxPrice',
                'v.price * (1 - COALESCE(v.offVariant, 0) / 100) <= :maxPrice'
            ))->setParameter('maxPrice', $search->getMaxPrice());
        }
    
        return $qb->getQuery()->getResult();
    }
    
    
    
    
    
    
    


//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
