<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;

class ReportingService
{
    public function __construct(
        private PortfolioService $portfolioService,
        private StructureService $structureService
    ) {}

    public function getPortfolioSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();
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