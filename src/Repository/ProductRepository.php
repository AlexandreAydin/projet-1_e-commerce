<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\SearchProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

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

    private Connection $connection;


    public function __construct(ManagerRegistry $registry,Connection $connection)
    {
        parent::__construct($registry, Product::class);
        $this->connection = $connection;
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



    // public function findBySearchQuery(string $query, ?array $categories = null): array 
    // {
    //     $qb = $this->createQueryBuilder('p')
    //         ->leftJoin('p.productBrand', 'pb') // Joindre l'entité ProductBrand
    //         ->leftJoin('p.brandModel', 'bm')   // Joindre l'entité BrandModel
    //         ->where('
    //             p.name LIKE :query OR
    //             p.description LIKE :query OR
    //             p.ean LIKE :query OR
    //             pb.name LIKE :query OR
    //             bm.name LIKE :query
    //         ')
    //         ->setParameter('query', '%' . $query . '%');
    
    //     if ($categories) {
    //         $qb->andWhere('p.categorie IN (:categories)')
    //            ->setParameter('categories', $categories);
    //     }
    
    //     return $qb->getQuery()->getResult();
    // }


    public function findBySearchQuery(string $query, ?array $categories = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.productBrand', 'pb')
            ->leftJoin('p.brandModel', 'bm')
            ->leftJoin('p.categorie', 'c')
            ->leftJoin('p.subCategorie', 'sc')
            ->where('
                p.name LIKE :query OR
                p.description LIKE :query OR
                p.description2 LIKE :query OR
                p.illustrationText1 LIKE :query OR
                p.ean LIKE :query OR
                c.name LIKE :query OR
                sc.name LIKE :query OR
                pb.name LIKE :query OR
                bm.name LIKE :query
            ')
            ->setParameter('query', '%' . $query . '%'); // Recherche avec correspondance partielle
    
        if ($categories) {
            $qb->andWhere('p.categorie IN (:categories)')
               ->setParameter('categories', $categories);
        }
    
        // return $qb->getQuery()->orderBy('p.id', 'DESC')->getResult();
        return $qb
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    


    public function findProductsBySimilar(string $query): array
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();
    
        // Prepare the search term for LIKE conditions and Levenshtein comparisons
        $likeQuery = '%' . $query . '%';
        $cleanedQuery = strtolower(str_replace(' ', '', $query));
    
        // Construct the SQL query using dynamic parameters correctly
        $sql = "
            SELECT DISTINCT p.*, 
                LEVENSHTEIN(LOWER(p.name), :cleanedQuery) AS levenshtein_distance,
                LEVENSHTEIN(LOWER(pb.name), :cleanedQuery) AS brand_levenshtein_distance,
                LEVENSHTEIN(LOWER(bm.name), :cleanedQuery) AS model_levenshtein_distance,
                LEVENSHTEIN(LOWER(c.name), :cleanedQuery) AS category_levenshtein_distance,
                LEVENSHTEIN(LOWER(sc.name), :cleanedQuery) AS subcategory_levenshtein_distance,
                LEVENSHTEIN(LOWER(p.description), :cleanedQuery) AS description_levenshtein_distance
                FROM product p
                    LEFT JOIN product_brand pb ON p.product_brand_id = pb.id
                    LEFT JOIN brand_model bm ON p.brand_model_id = bm.id
                    LEFT JOIN categorie c ON p.categorie_id = c.id
                    LEFT JOIN sub_categorie sc ON p.sub_categorie_id = sc.id
                WHERE 
                    LEVENSHTEIN(LOWER(p.name), :cleanedQuery) <= :thresholdQuery
                    OR LEVENSHTEIN(LOWER(pb.name), :cleanedQuery) <= :threshold
                    OR LEVENSHTEIN(LOWER(bm.name), :cleanedQuery) <= :threshold
                    OR LEVENSHTEIN(LOWER(c.name), :cleanedQuery) <= :threshold
                    OR LEVENSHTEIN(LOWER(sc.name), :cleanedQuery) <= :threshold
                    OR LEVENSHTEIN(LOWER(p.description), :cleanedQuery) <= :threshold
                    OR LOWER(p.name) LIKE :likeQuery
                    OR LOWER(pb.name) LIKE :likeQuery
                    OR LOWER(bm.name) LIKE :likeQuery
                    OR LOWER(c.name) LIKE :likeQuery
                    OR LOWER(sc.name) LIKE :likeQuery
                    OR LOWER(p.description) LIKE :likeQuery
                    OR LOWER(p.description2) LIKE :likeQuery
                    OR LOWER(p.illustration_text1) LIKE :likeQuery
                ORDER BY levenshtein_distance DESC, brand_levenshtein_distance DESC, model_levenshtein_distance DESC,
                    category_levenshtein_distance DESC, subcategory_levenshtein_distance DESC,description_levenshtein_distance DESC;
        ";

    

    
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('cleanedQuery', $cleanedQuery);
        $stmt->bindValue('likeQuery', $likeQuery);
        $stmt->bindValue('threshold', 3);  // Adjust this threshold based on your needs
        $stmt->bindValue('thresholdQuery', 5);  // Adjust this threshold based on your needs
        
        $result = $stmt->executeQuery();
    
        $results = [];
        while ($row = $result->fetchAssociative()) {
            $results[] = $row;
        }
    
        return $results;
    }
    
    
    
    
    
    
    
    

    
    
    

    // a ajouter dans php myadmin pour chercher au nom pres du produit
    
//     DELIMITER $$

// CREATE FUNCTION LEVENSHTEIN(s1 VARCHAR(255), s2 VARCHAR(255)) RETURNS INT
// DETERMINISTIC
// BEGIN
//     DECLARE s1_len, s2_len, i, j, cost INT;
//     DECLARE d TEXT;
//     DECLARE result INT;

//     SET s1_len = CHAR_LENGTH(s1);
//     SET s2_len = CHAR_LENGTH(s2);

//     IF s1_len = 0 THEN RETURN s2_len; END IF;
//     IF s2_len = 0 THEN RETURN s1_len; END IF;

//     -- Initialise le tableau comme une chaîne de caractères
//     SET d = REPEAT('0', (s1_len + 1) * (s2_len + 1));

//     -- Remplit la première ligne
//     SET i = 0;
//     WHILE i <= s1_len DO
//         SET d = INSERT(d, i * (s2_len + 1) + 1, 1, CHAR(i));
//         SET i = i + 1;
//     END WHILE;

//     -- Remplit la première colonne
//     SET j = 0;
//     WHILE j <= s2_len DO
//         SET d = INSERT(d, j + 1, 1, CHAR(j));
//         SET j = j + 1;
//     END WHILE;

//     -- Calcul de la distance de Levenshtein
//     SET i = 1;
//     WHILE i <= s1_len DO
//         SET j = 1;
//         WHILE j <= s2_len DO
//             SET cost = IF(SUBSTRING(s1, i, 1) = SUBSTRING(s2, j, 1), 0, 1);
//             SET result = LEAST(
//                 ORD(SUBSTRING(d, (i - 1) * (s2_len + 1) + j + 1, 1)) + 1,
//                 ORD(SUBSTRING(d, i * (s2_len + 1) + j - 1, 1)) + 1,
//                 ORD(SUBSTRING(d, (i - 1) * (s2_len + 1) + j - 1, 1)) + cost
//             );
//             SET d = INSERT(d, i * (s2_len + 1) + j + 1, 1, CHAR(result));
//             SET j = j + 1;
//         END WHILE;
//         SET i = i + 1;
//     END WHILE;

//     RETURN ORD(SUBSTRING(d, s1_len * (s2_len + 1) + s2_len + 1, 1));
// END $$

// DELIMITER ;




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
    
        // return $qb->getQuery()->getResult();
        return $qb
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
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
