<?php

namespace App\Repository;

use App\Entity\Product;
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
        if (!empty($search->string)) {
            $query = $query
                ->andWhere('p.name LIKE :string')
                ->setParameter('string', "%{$search->string}%");
        }
    
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
