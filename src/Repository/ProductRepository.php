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
     $query = $this->createQueryBuilder('p');

    //Price
    if ($search->getMinPrice()) {
        $query = $query->andWhere('p.price >= ' . $search->getMinPrice()*100);
    }

     if($search->getMaxPrice()){
        $query= $query->andWhere('p.price <=' .$search->getMaxPrice()*100);
    }

    // Tags
    if($search->getTags()){
        $query = $query->andWhere('p.tags like :val')
                ->setParameter('val', "%{$search->getTags()}%");
    }

    // Categories
    if($search->getCategories()){
            $query = $query->join('p.categorie','c')
                            ->andWhere('c.id IN (:categories)')
                            ->setParameter('categories',$search->getCategories());
    }

    // dd( $query->getQuery());
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
