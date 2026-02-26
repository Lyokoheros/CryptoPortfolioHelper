<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;
use App\Service\PricesService;

class PortfolioService
{
    private $avaibleParameterFunctions = [
        'getAssetValueInPortfolio',
        'getAssetCostInPortfolioPerCurrency',
        'getAssetTotalCostInPortfolio',
        'getAssetsRealizedIncomeInPortfolio'
    ];

    public function __construct(
        private CurrencyService $currencyService,
        private AssetService $assetService
    ) {
        $this->avaibleParameterFunctions = array_flip($this->avaibleParameterFunctions);
    }

    public function getAssetValueInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = [])
    {          
        return $this->assetService->getAssetQuantity($asset, [$portfolio], $optionalCriteria) * $this->currencyService->getPrice($asset, $baseCurrency);
    }

    public function getPortfolioTotalsPerCriterium(Portfolio $portfolio, Currency $baseCurrency, string $criteriumFunction, array $optionalCriteria = []): float
    {
        if(!isset($this->avaibleParameterFunctions[$criteriumFunction]))
        {
            throw new \InvalidArgumentException("Function '$criteriumFunction' is not allowed");
        }
        $portfolioTotal = 0;
        $assets = $portfolio->getBoughtAssets();
        foreach($assets as $asset)
        {
            $portfolioTotal += $this->$criteriumFunction(
                $asset,
                $portfolio,
                $baseCurrency,
                $optionalCriteria);
        }
        return $portfolioTotal;
    }

    public function getCurrentPortfolioValue(Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = []): float
    {
        return $this->getPortfolioTotalsPerCriterium(
            $portfolio, 
            $baseCurrency, 
            'getAssetValueInPortfolio',
            $optionalCriteria);
    }

    public function getPortfolioTotalCost(Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = []): float
    {
        return $this->getPortfolioTotalsPerCriterium(
            $portfolio, 
            $baseCurrency, 
            'getAssetTotalCostInPortfolio',
            $optionalCriteria);
    }

    public function getTotalRealizedIncome(Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = []): float
    {
        return $this->getPortfolioTotalsPerCriterium(
            $portfolio, 
            $baseCurrency, 
            'getAssetsRealizedIncomeInPortfolio',
            $optionalCriteria);
    }

    public function getAssetCostInPortfolioPerCurrency(Currency $asset, Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = []): float
    {
        return $this->assetService->getAssetExpenses(
            $baseCurrency,
            [$portfolio], 
            [
                ...['boughtCurrency' => $asset],
                ...$optionalCriteria
            ]
        );
    }

    public function getAssetTotalCostInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = []): float
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
                    $purchaseCurrency,
                    $optionalCriteria
                ) * $this->currencyService->getPrice(
                    $purchaseCurrency, 
                    $baseCurrency
                );
                $totalCost += $assetCost;    
            }
        }
        return $totalCost;        
    }

    public function getAssetAvgPriceInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = []): float
    {
        $totalCost = $this->getAssetTotalCostInPortfolio($asset, $portfolio, $baseCurrency, $optionalCriteria);
        $assetQuantity = $this->assetService->getAssetIncome($asset, [$portfolio], $optionalCriteria);
        if($assetQuantity > 0)
        {
            return $totalCost / $assetQuantity;
        }
        return 0;
    }

    public function getSoldOutPercentageInPortfolio(Currency $asset, Portfolio $portfolio, array $optionalCriteria = []): float
    {
        $assetIncome = $this->assetService->getAssetIncome($asset, [$portfolio], $optionalCriteria);
        $assetExpenses = $this->assetService->getAssetExpenses($asset, [$portfolio], $optionalCriteria);
        if($assetIncome > 0)
        {
            return ($assetExpenses / $assetIncome);
        }
        return 0;
    }

    public function getAssetsRealizedIncomeInPortfolio(Currency $asset, Portfolio $portfolio, Currency $baseCurrency, array $optionalCriteria = []): float
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
                    [
                        ...['soldCurrency' => $asset], 
                        ...$optionalCriteria
                ]) * $this->currencyService->getPrice(
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

    public function getAllAssetsStatInPortfolio(Portfolio $portfolio, Currency $baseCurrency): array
    {   
        $assetsStats = [];
        $allAssets = $portfolio->getBoughtAssets();
        foreach($allAssets as $asset)
        {
            $assetsStats[$asset->getSymbol()] = $this->getAssetStatInPortfolio(
                $asset, 
                $portfolio,
                $baseCurrency);
        }

        return $assetsStats;
    }

    public function getTopPerformer(Portfolio $portfolio, Currency $baseCurrency, string $parameter = 'valueBalance')
    {
        $assets = $portfolio->getBoughtAssets();
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
                    'asset' => $asset->getSymbol(),
                    'measuredStat' => $parameter,
                    'value' => $topValue,
                    'stat' => $assetStat
                ];
            }
        }
        return $topPerformer;
    }

    public function getBottomPerformer(Portfolio $portfolio, Currency $baseCurrency, string $parameter = 'valueBalance')
    {
        $assets = $portfolio->getBoughtAssets();
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
                    'asset' => $asset->getSymbol(),
                    'measuredStat' => $parameter,
                    'value' => $bottomValue,
                    'stat' => $assetStat
                ];
            }
        }
        return $bottomPerformer;
    }

}