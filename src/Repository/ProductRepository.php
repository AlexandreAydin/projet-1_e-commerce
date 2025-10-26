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


    public function findByBrandSlug($slug)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.productBrand', 'pb')
            ->where('pb.slug = :slug')
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
        $em   = $this->getEntityManager();
        $conn = $em->getConnection();

        // --- Normalisation PHP (pour scoring / corrections) ---
        $normalize = static function (?string $s): string {
            $s = (string)$s;
            $s = mb_strtolower($s);
            if (class_exists(\Transliterator::class)) {
                if ($tr = \Transliterator::create('Any-Latin; Latin-ASCII')) {
                    $s = $tr->transliterate($s);
                }
            }
            // garder uniquement [a-z0-9]
            return preg_replace('/[^a-z0-9]+/u', '', $s) ?? '';
        };

        // --- Corrections "contient" (substr) sur la version normalisée ---
        // clé = faute fréquente (normalisée), valeur = remplacement (normalisé)
        $typoSubstrings = [
            'palstation'  => 'playstation',
            'plaistation' => 'playstation',
            'plastation'  => 'playstation',
            'playstion'   => 'playstation',
            'playstaton'  => 'playstation',
            'oralb'       => 'oralb',     // on corrige plus loin vers "oral b" pour l'affichage
        ];

        $qNorm = $normalize($query);              // ex: "plastation 5" -> "plastation5"
        $orig  = $query;

        foreach ($typoSubstrings as $needle => $replacement) {
            if (strpos($qNorm, $needle) !== false) {
                // remplace DANS qNorm
                $qNorm = str_replace($needle, $replacement, $qNorm);
            }
        }

        // Ajustement d'affichage : si on a "oralb" normalisé, proposer "oral b" côté brut
        if ($qNorm === 'oralb') {
            $query = 'oral b';
        } else {
            // si 'playstation' se trouve dans qNorm et il y a un chiffre à la fin, on injecte un espace pour l'affichage
            if (preg_match('/^(playstation)(\d+)$/', $qNorm, $m)) {
                $query = $m[1] . ' ' . $m[2]; // "playstation 5"
            }
        }

        // Motifs SQL
        $likeRaw  = '%'.mb_strtolower($query).'%'; // ex: "%playstation 5%"
        $likeNorm = '%'.$qNorm.'%';                // ex: "%playstation5%"

        // Ancres gauche/droite (filet) : robustes aux fautes d'1 caractère
        $len   = mb_strlen($qNorm);
        $left  = $len >= 4 ? mb_substr($qNorm, 0, 4) : $qNorm;                 // ex "play"
        $right = $len >= 4 ? mb_substr($qNorm, max(0, $len - 4)) : $qNorm;     // ex "tion5"
        $useAnchors = ($len >= 6 && $left !== '' && $right !== '');

        // Helper compact (MySQL 8+)
        $compact = static function (string $col): string {
            return "REGEXP_REPLACE(LOWER(COALESCE($col,'')),'[^a-z0-9]','')";
        };

        // --- 1) Pool de candidats ---
        $sql = "
            SELECT
                p.id, p.name, p.description, p.description2, p.illustration_text1,
                pb.name AS brand_name, bm.name AS model_name, c.name AS category_name, sc.name AS subcategory_name
            FROM product p
            LEFT JOIN product_brand pb ON p.product_brand_id = pb.id
            LEFT JOIN brand_model bm   ON p.brand_model_id   = bm.id
            LEFT JOIN categorie c      ON p.categorie_id     = c.id
            LEFT JOIN sub_categorie sc ON p.sub_categorie_id = sc.id
            WHERE
                -- LIKE brut
                LOWER(COALESCE(p.name,''))               LIKE :likeRaw
            OR LOWER(COALESCE(pb.name,''))              LIKE :likeRaw
            OR LOWER(COALESCE(bm.name,''))              LIKE :likeRaw
            OR LOWER(COALESCE(c.name,''))               LIKE :likeRaw
            OR LOWER(COALESCE(sc.name,''))              LIKE :likeRaw
            OR LOWER(COALESCE(p.description,''))        LIKE :likeRaw
            OR LOWER(COALESCE(p.description2,''))       LIKE :likeRaw
            OR LOWER(COALESCE(p.illustration_text1,'')) LIKE :likeRaw

            -- LIKE compact (supprime tout sauf [a-z0-9])
            OR {$compact('p.name')}               LIKE :likeNorm
            OR {$compact('pb.name')}              LIKE :likeNorm
            OR {$compact('bm.name')}              LIKE :likeNorm
            OR {$compact('c.name')}               LIKE :likeNorm
            OR {$compact('sc.name')}              LIKE :likeNorm
            OR {$compact('p.description')}        LIKE :likeNorm
            OR {$compact('p.description2')}       LIKE :likeNorm
            OR {$compact('p.illustration_text1')} LIKE :likeNorm

            -- Filet d'ancres : %left% ET %right% dans la version compactée
            ".($useAnchors ? "
            OR (
                {$compact('p.name')}               LIKE :left AND {$compact('p.name')}               LIKE :right
            ) OR (
                {$compact('pb.name')}              LIKE :left AND {$compact('pb.name')}              LIKE :right
            ) OR (
                {$compact('bm.name')}              LIKE :left AND {$compact('bm.name')}              LIKE :right
            ) OR (
                {$compact('c.name')}               LIKE :left AND {$compact('c.name')}               LIKE :right
            ) OR (
                {$compact('sc.name')}              LIKE :left AND {$compact('sc.name')}              LIKE :right
            ) OR (
                {$compact('p.description')}        LIKE :left AND {$compact('p.description')}        LIKE :right
            ) OR (
                {$compact('p.description2')}       LIKE :left AND {$compact('p.description2')}       LIKE :right
            ) OR (
                {$compact('p.illustration_text1')} LIKE :left AND {$compact('p.illustration_text1')} LIKE :right
            )
            " : "")."
            LIMIT 500
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('likeRaw',  $likeRaw);
        $stmt->bindValue('likeNorm', $likeNorm);
        if ($useAnchors) {
            $stmt->bindValue('left',  '%'.$left.'%');
            $stmt->bindValue('right', '%'.$right.'%');
        }
        $rows = $stmt->executeQuery()->fetchAllAssociative();

        if (!$rows) {
            return [];
        }

        // --- 2) Scoring Levenshtein (même normalisation que qNorm) ---
        $levShort = 3; // marque/modèle/catégorie
        $levLong  = 5; // nom/description
        $scored   = [];

        foreach ($rows as $r) {
            $fields = [
                'name'        => $r['name'] ?? '',
                'brand'       => $r['brand_name'] ?? '',
                'model'       => $r['model_name'] ?? '',
                'category'    => $r['category_name'] ?? '',
                'subcategory' => $r['subcategory_name'] ?? '',
                'desc'        => $r['description'] ?? '',
            ];
            $norm = array_map($normalize, $fields);

            $d = [];
            foreach ($norm as $k => $v) {
                $d[$k] = $v !== '' ? levenshtein($qNorm, $v) : 9999;
            }

            $keep =
                $d['name']        <= $levLong ||
                $d['desc']        <= $levLong ||
                $d['brand']       <= $levShort ||
                $d['model']       <= $levShort ||
                $d['category']    <= $levShort ||
                $d['subcategory'] <= $levShort;

            if ($keep) {
                $score = min(
                    $d['name'],
                    $d['desc'] + 1,
                    $d['brand'] + 1,
                    $d['model'] + 1,
                    $d['category'] + 2,
                    $d['subcategory'] + 2
                );
                $scored[] = ['product' => $r, 'score' => $score];
            }
        }

        if (!$scored) {
            return $rows; // fallback UX
        }

        usort($scored, fn($a, $b) => $a['score'] <=> $b['score']);
        return array_map(fn($s) => $s['product'], $scored);
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
            ->leftJoin('p.productBrand', 'pb')
            ->leftJoin('p.brandModel', 'bm')
            ->leftJoin('p.categorie', 'c')
            ->leftJoin('p.subCategorie', 'sc')
            ->orderBy('p.name', 'ASC');
    
        if ($query) {
            $qb->andWhere($qb->expr()->orX(
                'p.name LIKE :query',
                'p.description LIKE :query',
               ' p.description2 LIKE :query',
               ' p.illustrationText1 LIKE :query',
                'p.ean LIKE :query',
               ' c.name LIKE :query',
               ' sc.name LIKE :query',
                'pb.name LIKE :query',
                'bm.name LIKE :query'
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
