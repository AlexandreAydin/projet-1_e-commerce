<?php

namespace App\Repository;

use App\Entity\BrandModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BrandModel>
 */
class BrandModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrandModel::class);
    }

    // public function findSubcategoriesByCategoryIds(array $categoryIds): array
    // {
    //     $qb = $this->createQueryBuilder('sc')
    //         ->select('sc.id, sc.name') // Limitez aux champs nécessaires
    //         ->join('sc.categories', 'c')
    //         ->where('c.id IN (:categoryIds)')
    //         ->setParameter('categoryIds', $categoryIds);
    
    //     return $qb->getQuery()->useResultCache(true, 3600, 'subcategories_cache')->getArrayResult();
    // }
    
    public function findBrandModelByProductBrandIds(array $brandIds): array
    {
        // Construction de la requête
        return $this->createQueryBuilder('bm')
            ->select('bm.id, bm.name') // Sélection des champs nécessaires
            ->join('bm.productBrands', 'pb') // Correction du nom de la relation
            ->where('pb.id IN (:brandIds)')
            ->setParameter('brandIds', $brandIds)
            ->getQuery()
            ->useResultCache(true, 3600, 'brandmodel_cache') // Activation du cache pour optimiser
            ->getArrayResult(); // Conversion en tableau pour une réponse JSON
    }
    

//    /**
//     * @return BrandModel[] Returns an array of BrandModel objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BrandModel
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
