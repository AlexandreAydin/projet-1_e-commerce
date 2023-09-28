<?php

namespace App\Repository;

use App\Entity\RewiewsProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RewiewsProduct>
 *
 * @method RewiewsProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method RewiewsProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method RewiewsProduct[]    findAll()
 * @method RewiewsProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RewiewsProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RewiewsProduct::class);
    }

    public function save(RewiewsProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RewiewsProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAverageRatingForProduct($product)
    {
        $reviews = $this->findBy(['product' => $product]);
        $totalRating = array_sum(array_map(fn($review) => $review->getNote(), $reviews));
        $averageRating = (count($reviews) > 0) ? $totalRating / count($reviews) : 0;

        return $averageRating;
    }

//    /**
//     * @return RewiewsProduct[] Returns an array of RewiewsProduct objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?RewiewsProduct
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
