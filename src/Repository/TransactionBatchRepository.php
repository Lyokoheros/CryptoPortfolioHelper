<?php

namespace App\Repository;

use App\Entity\Portfolio;
use App\Entity\TransactionBatch;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<TransactionBatch>
 */
class TransactionBatchRepository extends EnhancedEntityRepository
{
    

    public function __construct(
        ManagerRegistry $registry,
        private PortfolioRepository $portfolioRepo
    ) {
        parent::__construct($registry);
    }


    public function addTransactionBatch($transactionBatchData): TransactionBatch
    {
        $transactionBatch = new TransactionBatch();

        if(!isset($transactionBatchData['portfolioId']) && !isset($transactionBatchData['portfolio']))
        {
            throw new \RuntimeException('Portfolio (or portfolioId) is required to create a Transaction Batch');
        }
        $transactionBatchData['date'] = $transactionBatchData['date'] ?? '';
        

        $this->entityManager->persist($transactionBatch);

        $this->editTransactionBatch(
            $transactionBatch->getId(), 
            $transactionBatchData,
            $transactionBatch
        );
        return $transactionBatch;
    }   


    public function editTransactionBatch(
        $id, $transactionBatchData,
        $transactionBatch = null
    ): void {
        if($transactionBatch === null)
        {
            $transactionBatch = $this->find($id);
        }

        if(isset($transactionBatchData['portfolioId']))
        {
            $portfolio = $this->portfolioRepo->find($transactionBatchData['portfolioId']);
            $transactionBatch->setPortfolio($portfolio);
        }
        if(isset($transactionBatchData['date']))
        {
            $transactionBatchData['date'] = new \DateTime($transactionBatchData['date']);
        }        

        $this->editEntity($transactionBatch, $transactionBatchData);
    }
    
    public function findOrCreateBatch(string $batchName, array $batchData): TransactionBatch
    {
        return $this->findOneBy(['name' => $batchName]) ?? $this->addTransactionBatch([
            ...['name' => $batchName],
            ...$batchData
        ]);
    }
}
