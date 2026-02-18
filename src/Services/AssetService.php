<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use App\Entity\User;
use App\Service\PricesService;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class AssetService
{
    private $currencyRepo;
    private $portfolioRepo;
    private $transactionBatchRepository;
    private $transactionRepository;


    public function __construct(ManagerRegistry $registry, private CurrencyService $currencyService)
    {
        $this->currencyRepo = $registry->getManager()->getRepository(Currency::class);
        $this->portfolioRepo = $registry->getManager()->getRepository(Portfolio::class);
        $this->transactionBatchRepository = $registry->getManager()->getRepository(TransactionBatch::class);
        $this->transactionRepository = $registry->getManager()->getRepository(Transaction::class);
        $this->currencyService = $currencyService;
    }


    public function getAssetIncome(Currency $asset, array $portfolios, $optionalCriteria = []): float
    {
        $assetIncome = 0; 
        foreach($portfolios as $portfolio)
        {
            $transactionBatchess = $this->transactionBatchRepository->findBy(['portfolio' => $portfolio]);

            foreach($transactionBatchess as $transactionBatch)
            {
                $transactions = $this->transactionRepository->findBy(
                    [
                        ...['transactionBatch' => $transactionBatch, 'boughtCurrency' => $asset],
                        ...$optionalCriteria //for using in customizable specific querries like only for specific exchange
                    ]    
                );
                foreach($transactions as $transaction)
                {  
                    $assetQuantity += $transaction->getBuyValue();
                    if($transaction->getFeeCurrency() && $transaction->getFeeCurrency() === $asset)
                    {
                        $assetIncome -= $transaction->getFee();
                    }
                }
            }
        }
        return $assetIncome;
    }

    public function getAssetExpenses(Currency $asset, array $portfolios, $optionalCriteria = []): float
    {
        $assetExpenses = 0; 
        foreach($portfolios as $portfolio)
        {
            $transactionBatchess = $this->transactionBatchRepository->findBy(['portfolio' => $portfolio]);

            foreach($transactionBatchess as $transactionBatch)
            {
                $transactions = $this->transactionRepository->findBy(
                    [
                        ...['transactionBatch' => $transactionBatch, 'soldCurrency' => $asset],
                        ...$optionalCriteria //for using in customizable specific querries like only for specific exchange
                    ]    
                );
                foreach($transactions as $transaction)
                {  
                    $assetExpenses += $transaction->getBuyValue();
                    if($transaction->getFeeCurrency() && $transaction->getFeeCurrency() === $asset)
                    {
                        $assetExpenses += $transaction->getFee();
                    }
                }
            }
        }
        return $assetExpenses;
    }
    
    public function getAssetQuantity(Currency $asset, array $portfolios, $optionalCriteria = []): float
    {
        return $this->getAssetIncome($asset, $portfolios, $optionalCriteria) 
            - $this->getAssetExpenses($asset, $portfolios, $optionalCriteria);
    }

    public function getBoughtAssets(Portfolio $portfolio): array
    {
        $assets = [];
        $transactionBatchess = $this->transactionBatchRepository->findBy(['portfolio' => $portfolio]);

        foreach($transactionBatchess as $transactionBatch)
        {
            $transactions = $this->transactionRepository->findBy(['transactionBatch' => $transactionBatch]);

            foreach($transactions as $transaction)
            {  
                $boughtAsset = $transaction->getBoughtCurrency();
                $assets[$boughtAsset->getId()] = $boughtAsset;
            }
        }
        return $assets;
    }

    public function getSoldAssets(Portfolio $portfolio): array
    {
        $assets = [];
        $transactionBatchess = $this->transactionBatchRepository->findBy(['portfolio' => $portfolio]);

        foreach($transactionBatchess as $transactionBatch)
        {
            $transactions = $this->transactionRepository->findBy(['transactionBatch' => $transactionBatch]);

            foreach($transactions as $transaction)
            {  
                $soldAsset = $transaction->getSoldCurrency();
                $assets[$soldAsset->getId()] = $soldAsset;
            }
        }
        return $assets;
    }
    
}