<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\Exchange;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<Transaction>
 */
class TransactionRepository extends EnhancedEntityRepository
{
    private $batchRepository;
    private $currencyRepository;
    private $exchangeRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
        $this->batchRepository = $this->entityManager->getRepository(TransactionBatch::class);
        $this->currencyRepository = $this->entityManager->getRepository(Currency::class);
        $this->exchangeRepository = $this->entityManager->getRepository(Exchange::class);
    }

    public function addTransaction($transactionData): void
    {
        $transaction = new Transaction();

        if(!isset($transactionData['batchId']) && !isset($transactionData['transactionBatch']))
        {
            throw new \RuntimeException('Transaction must be part of a batch (batchId missing)');
        }

        if(!$transactionData['boughtCurrencyId'] && !$transactionData['boughtCurrencySymbol'])
        {
            throw new \RuntimeException('Transaction must have a bought currency (boughtCurrencyId or boughtCurrencySymbol missing)');
        }
        if(!$transactionData['soldCurrencyId'] && !$transactionData['soldCurrencySymbol'])
        {
            throw new \RuntimeException('Transaction must have a sold currency (soldCurrencyId or soldCurrencySymbol missing)');
        }
        if(!$transactionData['exchangeId'] && !$transactionData['exchangeName'])
        {
            throw new \RuntimeException('Transaction must have an exchange (exchangeId or exchangeName missing)');
        }


        $transactionData['date'] = $transactionData['date'] ?? '';        

        $this->entityManager->persist($transaction);

        $this->editTransaction(
            $transaction->getId(), 
            $transactionData,
            $transaction
        );
    }   


    public function editTransaction(
        $id, $transactionData,
        $transaction = null
    ): void {
        if($transaction === null)
        {
            $transaction = $this->find($id);
        }

        if(isset($transactionData['batchId']))
        {
            $batch = $this->batchRepository->find($transactionData['batchId']);
            $transaction->setTransactionBatch($batch);
        }

        if(isset($transactionData['boughtCurrencyId']))
        {
            $boughtCurrency = $this->currencyRepository->find(
                $transactionData['boughtCurrencyId']
            );
            $transaction->setBoughtCurrency($boughtCurrency);
        }
        else if(isset($transactionData['boughtCurrencySymbol']))
        {
            $boughtCurrency = $this->currencyRepository->findOneBy([
                'symbol' => $transactionData['boughtCurrencySymbol']
            ]);           
            $transaction->setBoughtCurrency($boughtCurrency);
        }

        if(isset($transactionData['soldCurrencyId']))
        {
            $soldCurrency = $this->currencyRepository->find(
                $transactionData['soldCurrencyId']
            );
            $transaction->setSoldCurrency($soldCurrency);
        }
        else if(isset($transactionData['soldCurrencySymbol']))
        {
            $soldCurrency = $this->currencyRepository->findOneBy([
                'symbol' => $transactionData['soldCurrencySymbol']
            ]);           
            $transaction->setSoldCurrency($soldCurrency);
        }        

        if(isset($transactionData['feeCurrencyId']))
        {
            $feeCurrency = $this->currencyRepository->find(
                $transactionData['feeCurrencyId']
            );
            $transaction->setFeeCurrency($feeCurrency);
        }
        else if(isset($transactionData['feeCurrencySymbol']))
        {
            $feeCurrency = $this->currencyRepository->findOneBy([
                'symbol' => $transactionData['feeCurrencySymbol']
            ]);           
            $transaction->setFeeCurrency($feeCurrency);
        }

        if(isset($transactionData['exchangeId']))
        {
            $exchange = $this->exchangeRepository->find(
                $transactionData['exchangeId']
            );
            $transaction->setExchange($exchange);
        }
        else if(isset($transactionData['exchangeName']))
        {
            $exchange = $this->exchangeRepository->findOneBy([
                'name' => $transactionData['exchangeName']
            ]);           
            $transaction->setExchange($exchange);
        }

        if(isset($transactionData['date']))
        {
            $transactionData['date'] = new \DateTime($transactionData['date']);
        }

        $this->editEntity($transaction, $transactionData);
    }
}
