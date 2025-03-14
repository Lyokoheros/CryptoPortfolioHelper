<?php

namespace App\Repository;

use App\Entity\TransactionBatch;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<TransactionBatch>
 */
class TransactionBatchRepository extends EnhancedEntityRepository
{
    /*public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionBatch::class);
    }*/

    //    /**
    //     * @return TransactionBatch[] Returns an array of TransactionBatch objects
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

    //    public function findOneBySomeField($value): ?TransactionBatch
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
