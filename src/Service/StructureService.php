<?php

namespace App\Service;

use App\Entity\Currency;

class StructureService
{
    private $avaibleParameterFunctions = [
        'getAssetValueInPortfolio',
        'getAssetCostInPortfolioPerCurrency',
        'getAssetTotalCostInPortfolio',
        'getAssetsRealizedIncomeInPortfolio',
        'getSoldOutPercentageInPortfolio'
    ];

    public function __construct(
        private AssetService $assetService,
        private PortfolioService $portfolioService
    ) {
        $this->avaibleParameterFunctions = array_flip($this->avaibleParameterFunctions);
    }

    public function getAssetStructurePerParameter(array $portfolios, Currency $baseCurrency, string $functionName, $optionalCriteria = []): array
    {
        $structure = [];
        $totalValue = 0;
        if(!isset($this->avaibleParameterFunctions[$functionName]))
        {
            throw new \InvalidArgumentException("Function '$functionName' is not allowed");
        }

        
        foreach($portfolios as $portfolio)
        {
            $assets = $portfolio->getBoughtAssets();
            foreach($assets as $asset)
            {
                $assetValue = $this->portfolioService->$functionName(
                    $asset,
                    $portfolio, 
                    $baseCurrency, 
                    $optionalCriteria
                );
                $structure[$asset->getSymbol()]['value'] = ($structure[$asset->getSymbol()] ?? 0) 
                        + $assetValue;
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

    public function getSoldOutStructure(array $portfolios, Currency $baseCurrency, $optionalCriteria = []): array
    {
        return $this->getAssetStructurePerParameter(
            $portfolios,
            $baseCurrency,
            'getSoldOutPercentageInPortfolio',
            $optionalCriteria
        );
    }
}