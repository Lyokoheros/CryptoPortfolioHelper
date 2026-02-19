<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use App\Entity\User;
use App\Service\PricesService;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class PortfolioService
{
    private $currencyRepo;
    private $portfolioRepo;
    private $transactionBatchRepository;
    private $transactionRepository;


    public function __construct(
        ManagerRegistry $registry, 
        private CurrencyService $currencyService,
        private AssetService $assetService
    ) {
        $this->currencyRepo = $registry->getManager()->getRepository(Currency::class);
        $this->portfolioRepo = $registry->getManager()->getRepository(Portfolio::class);
        $this->transactionBatchRepository = $registry->getManager()->getRepository(TransactionBatch::class);
        $this->transactionRepository = $registry->getManager()->getRepository(Transaction::class);
        //$this->currencyService = $currencyService;
    }

    public function getAssetValueInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency)
    {          
        return $this->assetService->getAssetQuantity($asset, [$portfolio]) * $this->currencyService->getPrice($asset, $baseCurrency);
    }

    public function getCurrentPortfolioValue(Portfolio $portfolio, Currency $baseCurrency): float
    {
        $portfolioValue = 0;
        $assets = $this->assetService->getBoughtAssets($portfolio);
        foreach($assets as $asset)
        {
            $portfolioValue += $this->getAssetValueInPortfolio($asset, $portfolio, $baseCurrency);
        }
        return $portfolioValue;
    }

    public function getAssetCostInPortfolioPerCurrency(Currency $asset, Portfolio $portfolio, Currency $baseCurrency): float
    {
        return $this->assetService->getAssetExpenses(
            $baseCurrency,
            [$portfolio], 
            ['boughtCurrency' => $asset]
        );
    }

    public function getAssetTotalCostInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency): float
    {
        $purchaseCurrencies = $this->assetService->getSoldAssets($portfolio);
        $totalCost = 0;

        foreach($purchaseCurrencies as $purchaseCurrency)
        {
            if($purchaseCurrency->isInNiches(['stablecoins', 'fiat']))
            {
                $assetCost = $this->getAssetCostInPortfolioPerCurrency(
                    $asset, 
                    $portfolio, 
                    $purchaseCurrency
                ) * $this->currencyService->getPrice(
                    $purchaseCurrency, 
                    $baseCurrency
                );
                $totalCost += $assetCost;    
            }
        }
        return $totalCost;        
    }

    public function getAssetAvgPriceInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency): float
    {
        $totalCost = $this->getAssetTotalCostInPortfolio($asset, $portfolio, $baseCurrency);
        $assetQuantity = $this->assetService->getAssetIncome($asset, [$portfolio]);
        if($assetQuantity > 0)
        {
            return $totalCost / $assetQuantity;
        }
        return 0;
    }

    public function getSoldOutPercentageInPortfolio(Currency $asset, Portfolio $portfolio): float
    {
        $assetIncome = $this->assetService->getAssetIncome($asset, [$portfolio]);
        $assetExpenses = $this->assetService->getAssetExpenses($asset, [$portfolio]);
        if($assetIncome > 0)
        {
            return ($assetExpenses / $assetIncome);
        }
        return 0;
    }

    public function getAssetsRealizedIncomeInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency): float
    {
        $income = 0;
        $sellCurrencies = $this->assetService->getSoldAssets($portfolio);
        foreach($sellCurrencies as $sellCurrency)
        {
            if($sellCurrency->isInNiches(['stablecoins', 'fiat']))
            {
                $income += $this->assetService->getAssetIncome(
                    $sellCurrency, 
                    [$portfolio], 
                    ['soldCurrency' => $asset]
                ) * $this->currencyService->getPrice(
                    $sellCurrency, 
                    $baseCurrency
                );
            }
        }
        return $income;
    }

    public function getAssetStatInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency): array
    {
        $totalBought = $this->assetService->getAssetIncome($asset, [$portfolio]);
        $totalCost = $this->getAssetTotalCostInPortfolio($asset, $portfolio, $baseCurrency);
        $soldOutPercentage = $this->getSoldOutPercentageInPortfolio($asset, $portfolio);
        
        $avgPrice = $this->getAssetAvgPriceInPortfolio($asset, $portfolio, $baseCurrency);
        $realizedAvgPrice = $avgPrice;
        $currentPrice = $this->currencyService->getPrice($asset, $baseCurrency);
        $realizedIncome = $this->getAssetsRealizedIncomeInPortfolio($asset, $portfolio, $baseCurrency);
        $assetValue = $this->getAssetValueInPortfolio($asset, $portfolio, $baseCurrency);

        if($soldOutPercentage > 0)
        {
            $realizedAvgPrice = (1-$soldOutPercentage)*$currentPrice + ($realizedIncome/$totalBought); 
            //methematically second element is $soldOutPercentage*($realizedIncome/($soldOutPercentage*$totalBought)
        }
        $valueBalance = ($realizedIncome + $assetValue) / $totalCost;

        return [
            'assetSymbol' => $asset->getSymbol(),
            'totalBought' => $totalBought,
            'totalCost' => $totalCost,
            'avgBuyingPrice' => $avgPrice,
            'currentPrice' => $currentPrice,
            'realizedAvgPrice' => $realizedAvgPrice,
            'currentQuantity' => $this->assetService->getAssetQuantity($asset, [$portfolio]),
            'soldOutPercentage' => $soldOutPercentage,
            'realizedProfit' => $realizedIncome,
            'realizedProfitBalance' => $realizedIncome - $totalCost,
            'profitBalance' => $realizedIncome + $assetValue - $totalCost,
            'valueBalance' => $valueBalance,
            'currentAssetValue' => $assetValue
        ];
    }

    public function getTopPerformer(Portfolio $portfolio, Currency $baseCurrency, string $parameter = 'valueBalance')
    {
        $assets = $this->assetService->getBoughtAssets($portfolio);
        $topPerformer = null;
        $topValue = null;
        foreach($assets as $asset)
        {
            if($asset->isInNiches(['stablecoins', 'fiat']))
            {
                continue;
            }
            $assetStat = $this->getAssetStatInPortfolio($asset, $portfolio, $baseCurrency);
            if($topValue === null || $assetStat[$parameter] > $topValue)
            {
                $topValue = $assetStat[$parameter];
                $topPerformer = [
                    'asset' => $asset,
                    'stat' => $assetStat,
                    'value' => $topValue
                ];
            }
        }
        return $topPerformer;
    }

    public function getBottomPerformer(Portfolio $portfolio, Currency $baseCurrency, string $parameter = 'valueBalance')
    {
        $assets = $this->assetService->getBoughtAssets($portfolio);
        $bottomPerformer = null;
        $bottomValue = null;
        foreach($assets as $asset)
        {
            if($asset->isInNiches(['stablecoins', 'fiat']))
            {
                continue;
            }
            $assetStat = $this->getAssetStatInPortfolio($asset, $portfolio, $baseCurrency);
            if($bottomValue === null || $assetStat[$parameter] < $bottomValue)
            {
                $bottomValue = $assetStat[$parameter];
                $bottomPerformer = [
                    'asset' => $asset,
                    'stat' => $assetStat,
                    'value' => $bottomValue
                ];
            }
        }
        return $bottomPerformer;
    }

}