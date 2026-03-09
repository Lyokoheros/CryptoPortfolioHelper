<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;
use Psr\Log\LoggerInterface;

class ReportingService
{
    public function __construct(
        private AssetService $assetService,
        private CurrencyService $currencyService,
        private PortfolioService $portfolioService,
        private StructureService $structureService,
        private LoggerInterface $logger        
    ) {}

    private function logMemory(string $label): void
    {
        $usage = memory_get_usage(true) / 1024 / 1024;
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        $this->logger->info("[$label] Current: {$usage}MB | Peak: {$peak}MB");
    }

    public function getPortfolioValueSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {   
        //$this->currencyService->getCache();
        //$this->logMemory("summaryRetrieval start");
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();

        $this->currencyService->updatePriceInBullk($portfolio->getBoughtAssets());
        //$this->logMemory("price updated");

        $currentValue = $this->portfolioService->getCurrentPortfolioValue(
                $portfolio,
                $baseCurrency
            );
        //$this->logMemory("current value calculated");
        
        $valueStructure = $this->structureService->getAssetValueStructure(
                [$portfolio],
                $baseCurrency
            );
        //$this->logMemory("value structure calculated");
        
        $topPerformer = $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency
            );
        //$this->logMemory("top performer found");

        $bottomPerformer = $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency
            );            
        //$this->logMemory("bottom performer found");


        $topValuePerformer = $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency,
                'currentAssetValue'
            );
        //$this->logMemory("top performer found(current Value)");

        $bottomValuePerformer = $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency,
                'currentAssetValue'
            );
        //$this->logMemory("bottom performer found(current Value)");

        //$this->currencyService->getCache();
        return [
            'portfolioName' => $portfolio->getName(),
            'portfolioTotalValue' => $currentValue,
            'portfolioValueStructure' => $valueStructure,            
            'portfolioTopPerformer' => $topPerformer,
            'portfolioBottomPerformer' => $bottomPerformer,
            'portfolioTopValue' => $topValuePerformer,
            'portfolioBottomValue' => $bottomValuePerformer
        ];
    }

    public function getPortfolioCostSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();
        
        $this->currencyService->updatePriceInBullk($portfolio->getBoughtAssets());

        $purchaseCurrencies = $portfolio->getSoldAssets();
        //test
        foreach($purchaseCurrencies as $currency)
        {
            //echo $currency->getSymbol(). " ";
            $this->logMemory("purchase currency: ". $currency->getSymbol());        
        }
        //echo "\n";
        return [
            'portfolioName' => $portfolio->getName(),
            'portfolioTotalValue' => $this->portfolioService->getCurrentPortfolioValue(
                $portfolio,
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
            'portfolioTopPerformer' => $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency,
                'totalCost'
            ),
            'portfolioBottomPerformer' => $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency,
                'totalCost'
            )
        ];
    }

    public function getPortfolioCoinsSummary(Portfolio $portfolio, Currency $baseCurrency = null): array
    {
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();
        
        $this->currencyService->updatePriceInBullk($portfolio->getBoughtAssets());

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
        
        $this->currencyService->updatePriceInBullk($portfolio->getBoughtAssets());

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
                [$portfolio]
            )[$portfolio->getName()],
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