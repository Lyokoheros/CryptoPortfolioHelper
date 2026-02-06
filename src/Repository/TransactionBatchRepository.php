<?php

namespace App\Repository;

use App\Entity\TransactionBatch;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<TransactionBatch>
 */
class TransactionBatchRepository extends EnhancedEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }


    public function addTransactionBatch($transactionBatchData): void
    {
        $transactionBatch = new TransactionBatch();

        if($transactionBatchData['portfolioId'])
        {
            $portfolio = $this->entityManager
                ->getRepository('App\Entity\Portfolio')
                ->find($transactionBatchData['portfolioId']);
            $transactionBatch->setPortfolio($portfolio);
        }
        else
        {
            throw new \RuntimeException('Portfolio (portfolioId) is required to create a Transaction Batch');
        }

        $this->entityManager->persist($transactionBatch);

        $this->editTransactionBatch(
            $transactionBatch->getId(), 
            $transactionBatchData,
            $transactionBatch
        );
    }   


    public function editTransactionBatch(
        $id, $transactionBatchData,
        $transactionBatch = null
    ): void {
        if($transactionBatch === null)
        {
            $transactionBatch = $this->find($id);
        }

        if($transactionBatchData['portfolioId'])
        {
            $portfolio = $this->entityManager
                ->getRepository('App\Entity\Portfolio')
                ->find($transactionBatchData['portfolioId']);
            $transactionBatch->setPortfolio($portfolio);
        }

        $this->editEntity($transactionBatch, $transactionBatchData);
    }

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
