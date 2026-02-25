<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use App\Service\CryptoApi\CoinGeckoApi;
use App\Service\CryptoApi\CryptoApiProviderInterface;

class CurrencyService
{   
    public function __construct(
        private CurrencyRepository $currencyRepo,
        private CryptoApiProviderInterface $cryptoApi,
        private CoinGeckoApi $coinGecko
    ) {}

    public function updatePrice(Currency $currency): float
    {
        $now = new \DateTime();
        
        if ($this->shouldUpdatePrice($currency)) 
        {
            $price = $this->cryptoApi->getCryptoPrice($currency);
            $currency->setLastPriceUpdate($now);
        }
        else 
        {
            $price = $currency->getCurrentPrice();
        }
        $currency->setCurrentPrice($price);
        

        $this->currencyRepo->saveEntity($currency);
        return $price;
    }

    private function shouldUpdatePrice(Currency $currency): bool
    {
        $lastUpdate = $currency->getLastPriceUpdate();
        $tenMinutesAgo = (new \DateTime())->modify('-10 minutes');
        
        return $lastUpdate === null || $lastUpdate < $tenMinutesAgo;
    }

    public function updatePriceInBullk(array $currencies): void
    {
        foreach($currencies as $currency)
        {
            if($this->shouldUpdatePrice($currency))
            {
                $prices = $this->coinGecko->getCryptoPrices($currencies);
                $assets = $currencies;
                foreach($assets as $currency)
                {
                    $currency->setCurrentPrice($prices[$currency->getSymbol()]);
                    $this->currencyRepo->saveEntity($currency);
                }  

                break;                
            }
        }        
    }

    public function translateStableCoinToFiat(Currency $stableCoin): ?Currency
    {
        $fiats = $this->currencyRepo->findFiatCurrencies();

        foreach($fiats as $fiatCurrency)
        {
            $symbol = $fiatCurrency->getSymbol();
            if(str_contains($stableCoin->getSymbol(), $symbol))
            {
                return $fiatCurrency;
            }
        }
        return null;            
    }

    public function getStablesForFiat(Currency $fiatCurrency): array
    {
        $stableCoins = [];
        $stables = $this->currencyRepo->findStableCoins();
        $symbol = $fiatCurrency->getSymbol();
        foreach($stables as $stableCoin)
        {
            if(str_contains($stableCoin->getSymbol(), $symbol))
            {
                $stableCoins[] = $stableCoin;
            }
        }
        return $stableCoins;            
    }

    public function getPrice(Currency $asset, Currency $baseCurrency): ?float
    {
        $this->updatePrice($asset);
        $assetCurrencyId = $asset->getId();

        $currency = $this->currencyRepo->findOneBy([
            'id' => $assetCurrencyId,
            'pricesCurrency' => $baseCurrency
        ]);

        if($currency)
        {
            $this->updatePrice($currency);
            return $currency->getCurrentPrice();
        }
        else
        {
            $baseCurrencyId = $baseCurrency->getId();
            $currency = $this->currencyRepo->findOneBy([
                'id' => $baseCurrencyId, 
                'pricesCurrency' => $asset]);
            if($currency)
            {
                $this->updatePrice($currency);
                return (1.0 / $currency->getCurrentPrice());
            }
            else
            {
                $currency = $this->currencyRepo->findOneBy([
                    'id' => $asset->getPricesCurrency()->getId(),
                    'pricesCurrency' => $baseCurrency
                ]);
                if($currency)
                {
                    $this->updatePrice($currency);
                    return ($currency->getCurrentPrice() * $asset->getCurrentPrice());
                }
                else
                {
                    $currency = $this->currencyRepo->findOneBy([
                        'id' => $baseCurrency->getId(),
                        'pricesCurrency' => $asset->getPricesCurrency()
                    ]);
                    if($currency)
                    {
                        $this->updatePrice($currency);
                        return ($asset->getCurrentPrice() / $currency->getCurrentPrice());
                    }
                }
            }
        }
        return null;
    }

    public function updateCoinGeckoIds(): array //to be removed
    {
        $allCurrencies = $this->currencyRepo->findAll();
        $coinGeckoData = null;
        $updates = 0;
        $output = [];

        foreach($allCurrencies as $currency)
        {
            if($currency->getCoinGeckoID() === null || true)            
            {
                if($coinGeckoData === null)
                {
                    $coinGeckoData = $this->coinGecko->getApiCoinsList();
                }
            }
            $symbol = strtolower($currency->getSymbol());
            $coinGeckoData ??= [];
            foreach($coinGeckoData as $coinData)
            {
                if($coinData['symbol'] == $symbol)
                {
                    $currency->setCoinGeckoID($coinData['id']);
                    $this->currencyRepo->saveEntity($currency);
                    $updates++;
                    $output []= $symbol . " => " .$coinData['id'];
                }
            }
        }
        return [
            'updates' => $updates,
            'output' => $output
        ];
    }

    

}