<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;

class ReportingService
{
    public function __construct(
        private AssetService $assetService,
        private CurrencyService $currencyService,
        private PortfolioService $portfolioService,
        private StructureService $structureService
    ) {}

    public function getPortfolioValueSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();
        
        $this->currencyService->updatePriceInBullk($this->assetService->getBoughtAssets([$portfolio], onlyCrypto: true));

        return [
            'portfolioName' => $portfolio->getName(),
            'portfolioTotalValue' => $this->portfolioService->getCurrentPortfolioValue(
                $portfolio,
                $baseCurrency
            ),
            'portfolioValueStructure' => $this->structureService->getAssetValueStructure(
                [$portfolio],
                $baseCurrency
            ),            
            'portfolioTopPerformer' => $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency
            ),
            'portfolioBottomPerformer' => $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency
            ),
            'portfolioTopValue' => $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency,
                'currentAssetValue'
            ),
            'portfolioBottomValue' => $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency,
                'currentAssetValue'
            )
        ];
    }

    public function getPortfolioCostSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();
        
        $this->currencyService->updatePriceInBullk($this->assetService->getBoughtAssets([$portfolio], onlyCrypto: true));

        return [
            'portfolioName' => $portfolio->getName(),
            'portfolioTotalValue' => $this->portfolioService->getCurrentPortfolioValue(
                $portfolio,
                $baseCurrency
            ),            
            'portfolioCostStructure' => $this->structureService->getAssetCostStructure(
                [$portfolio],
                $baseCurrency
            ),
            'portfolioTopPerformer' => $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency,
                'totalBought'
            ),
            'portfolioBottomPerformer' => $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency,
                'totalBought'
            )
        ];
    }

    public function getPortfolioCoinsSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();
        
        $this->currencyService->updatePriceInBullk($this->assetService->getBoughtAssets([$portfolio], onlyCrypto: true));

        return [
            'portfolioName' => $portfolio->getName(),
            'coinsStatistics' => $this->portfolioService->getAllAssetsStatInPortfolio(
                $portfolio,
                $baseCurrency
            ),
            'portfolioTopPerformer' => $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency
            ),
            'portfolioBottomPerformer' => $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency
            )
        ];
    }



    //causes memory problems
    public function getPortfolioSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();
        
        $this->currencyService->updatePriceInBullk($this->assetService->getBoughtAssets([$portfolio], onlyCrypto: true));

        return [
            'portfolioName' => $portfolio->getName(),
            'portfolioTotalValue' => $this->portfolioService->getCurrentPortfolioValue(
                $portfolio,
                $baseCurrency
            ),
            'portfolioValueStructure' => $this->structureService->getAssetValueStructure(
                [$portfolio],
                $baseCurrency
            ),
            'portfolioTotalCost' => $this->portfolioService->getPortfolioTotalCost(
                $portfolio,
                $baseCurrency
            ),
            'portfolioCostStructure' => $this->structureService->getAssetCostStructure(
                [$portfolio],
                $baseCurrency
            ),
            'portfolioTotalRealizedProfit' => $this->portfolioService->getTotalRealizedIncome(
                $portfolio,
                $baseCurrency
            ),
            'portfolioRealizedProfitCStructure' => $this->structureService->getRealizedValueStructure(
                [$portfolio],
                $baseCurrency
            ),
            'portfolioSoldOutStructure' => $this->structureService->getSoldOutStructure(
                [$portfolio],
                $baseCurrency
            ),
            'portfolioTopPerformer' => $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency
            ),
            'portfolioBottomPerformer' => $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency
            )
        ];
    }

}