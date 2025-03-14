<?php

namespace App\Repository;

use App\Entity\DailyExchangeRate;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<DailyExchangeRate>
 */
class DailyExchangeRateRepository extends EnhancedEntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyExchangeRate::class);
    }*/

    //    /**
    //     * @return DailyExchangeRate[] Returns an array of DailyExchangeRate objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DailyExchangeRate
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
