<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Repository\CurrencyRepository;
use App\Service\CurrencyRatesService;
use Psr\Log\LoggerInterface;

class ReportingService
{
    public function __construct(
        private CurrencyService $currencyService,
        private PortfolioService $portfolioService,
        private StructureService $structureService,
        private TransactionRepository $transactionRepository,
        private CurrencyRepository $currencyRepository,
        private CurrencyRatesService $currencyRatesService,
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
        $baseCurrency ??= $portfolio->getUser()->getDisplayCurrency();

        $this->currencyService->updatePriceInBullk($portfolio->getBoughtAssets());
        
        $currentValue = $this->portfolioService->getCurrentPortfolioValue(
                $portfolio,
                $baseCurrency
            );
        $valueStructure = $this->structureService->getAssetValueStructure(
                [$portfolio],
                $baseCurrency
            );
        $topPerformer = $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency
            );
        $bottomPerformer = $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency
            );     
        $topValuePerformer = $this->portfolioService->getTopPerformer(
                $portfolio,
                $baseCurrency,
                'currentAssetValue'
            );
        $bottomValuePerformer = $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency,
                'currentAssetValue'
            );

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
            'buyTransactions' => $this->portfolioService->getBuyTransactionNumbers(
                $portfolio
            ),
            'sellTransactions' => $this->portfolioService->getSellTransactionNumbers(
                $portfolio
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
            'portfolioTotalCost' => $this->portfolioService->getPortfolioTotalCost(
                $portfolio,
                $baseCurrency
            ),
            'portfolioTotalRealizedProfit' => $this->portfolioService->getTotalRealizedIncome(
                $portfolio,
                $baseCurrency
            ),
            'portfolioValueStructure' => $this->structureService->getAssetValueStructure(
                [$portfolio],
                $baseCurrency
            ),
            'portfolioCostStructure' => $this->structureService->getAssetCostStructure(
                [$portfolio],
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
            'purchases' => $this->portfolioService->getBuyTransactionNumbers($portfolio),
            'sellings' => $this->portfolioService->getSellTransactionNumbers($portfolio),
            'portfolioBottomPerformer' => $this->portfolioService->getBottomPerformer(
                $portfolio,
                $baseCurrency
            )
        ];
    }

    public function getPitReportData(User $user, int $year): array
    {
        $nativeCurrency = $user->getNativeCurrency();
        $startDate = (new \DateTime())->setDate($year, 1, 1)->setTime(0, 0, 0);
        $endDate = (new \DateTime())->setDate($year, 12, 31)->setTime(23, 59, 59);

        $fiats = $this->currencyRepository->findFiatCurrencies();
        echo "Fetched fiat currencies: " . count($fiats) . "\n";
        foreach($fiats as $fiat) {    
            $transactions = $this->transactionRepository->getUsersAllTransactionsForAsset(
                $user, $fiat, [ 'startDate' => $startDate, 'endDate' => $endDate ]
            );
            foreach($transactions as $transaction) {
                $this->currencyRatesService->checkCurrencyRate(
                    $fiat,
                    $nativeCurrency,
                    $transaction->getDate()
                );
            }
        }
        echo "Ensured exchange rates for all fiat transactions.\n";
        $income = 0;
        $cost = 0;
        foreach($fiats as $fiat) {    
            $transactions = $this->transactionRepository->getUserAssetValueInNativeCurrency(
                $user, $fiat, ['startDate' => $startDate, 'endDate' => $endDate ], true, 'income'
            );
            foreach($transactions as $transaction) {
                $income += $transaction['totalValueInNative'];
            }
            echo "Calculated income for fiat: " . $fiat->getSymbol() . "= " . $income . "\n";
            $transactions = $this->transactionRepository->getUserAssetValueInNativeCurrency(
                $user, $fiat, [ 'startDate' => $startDate, 'endDate' => $endDate ], true, 'expenses'
            );
            foreach($transactions as $transaction) {
                $cost += $transaction['totalValueInNative'];
            }
            echo "Calculated expenses for fiat: " . $fiat->getSymbol() . "= " . $cost . "\n";
        }

        return ['totalIncome' => $income, 'totalCost' => $cost];
    }

}