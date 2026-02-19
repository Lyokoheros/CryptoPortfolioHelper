<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class AssetStructureService
{
    private $currencyRepo;
    private $portfolioRepo;
    private $transactionBatchRepository;
    private $transactionRepository;

    private $avaibleParameterFunctions = [
        'getAssetValueInPortfolio',
        'getAssetCostInPortfolioPerCurrency',
        'getAssetTotalCostInPortfolio',
        'getAssetsRealizedIncomeInPortfolio'
    ];

    public function __construct(
        ManagerRegistry $registry, 
        private CurrencyService $currencyService,
        private AssetService $assetService,
        private PortfolioService $portfolioService
    ) {
        $this->currencyRepo = $registry->getManager()->getRepository(Currency::class);
        $this->portfolioRepo = $registry->getManager()->getRepository(Portfolio::class);
        $this->transactionBatchRepository = $registry->getManager()->getRepository(TransactionBatch::class);
        $this->transactionRepository = $registry->getManager()->getRepository(Transaction::class);
        $this->avaibleParameterFunctions = array_flip($this->avaibleParameterFunctions);
        //$this->currencyService = $currencyService;
    }

    public function getAssetStructurePerParameter(array $portfolios, Currency $baseCurrency, string $functionName, $optionalCriteria = []): array
    {//to do - generalizacja (dowolna funkcja zamiast getAssetValueInPortfolio) + kryteria
        //CostStructure, RealizedGainsStructure 
        $structure = [];
        $totalValue = 0;
        $assets = $this->assetService->getBoughtAssets($portfolios, $optionalCriteria);
        if(!isset($this->avaibleParameterFunctions[$functionName]))
        {
            throw new \InvalidArgumentException("Function '$functionName' is not allowed");
        }

        
        foreach($portfolios as $portfolio)
        {
            foreach($assets as $asset)
            {
                $assetValue = $this->portfolioService->$functionName(
                    $asset,
                    $portfolio, 
                    $baseCurrency, 
                    $optionalCriteria
                );
                $structure[$asset->getSymbol()] = [
                    'asset' => $asset,
                    'value' => ($structure[$asset->getSymbol()]['value'] ?? 0) 
                        + $assetValue
                ];
                $totalValue += $assetValue;
            }   
        }

        foreach($structure as $assetSymbol => $data)
        {
            $structure[$assetSymbol]['percentage'] = $totalValue > 0 ? ($data['value'] / $totalValue) * 100 : 0;
        }        
        
       return $structure;
    }

    public function getAssetValueStructure(array $portfolios, Currency $baseCurrency, $optionalCriteria = []): array
    {
        return $this->getAssetStructurePerParameter(
            $portfolios,
            $baseCurrency,
            'getAssetValueInPortfolio',
            $optionalCriteria
        );
    }

    public function getAssetCostStructure(array $portfolios, Currency $baseCurrency, $optionalCriteria = []): array
    {
        return $this->getAssetStructurePerParameter(
            $portfolios,
            $baseCurrency,
            'getAssetTotalCostInPortfolio',
            $optionalCriteria
        );
    }

    public function getRealizedValueStructure(array $portfolios, Currency $baseCurrency, $optionalCriteria = []): array
    {
        return $this->getAssetStructurePerParameter(
            $portfolios,
            $baseCurrency,
            'getAssetsRealizedIncomeInPortfolio',
            $optionalCriteria
        );
    }

        
        


}