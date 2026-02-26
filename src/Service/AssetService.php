<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;
use App\Repository\CurrencyRepository;
use App\Repository\TransactionBatchRepository;
use App\Repository\TransactionRepository;

class AssetService
{
    public function __construct(
        private CurrencyRepository $currencyRepository,
        private TransactionBatchRepository $transactionBatchRepository,
        private TransactionRepository $transactionRepository
    ) {}

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
                    $assetIncome += $transaction->getBuyValue();
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
    
    public function getAssetQuantity(Currency $asset, array $portfolios, array $optionalCriteria = []): float
    {
        return $this->getAssetIncome($asset, $portfolios, $optionalCriteria) 
            - $this->getAssetExpenses($asset, $portfolios, $optionalCriteria);
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