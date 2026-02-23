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

    public function updateCoinGeckoIds(): int
    {
        $allCurrencies = $this->currencyRepo->findAll();
        $coinGeckoData = null;
        $updates = 0;

        foreach($allCurrencies as $currency)
        {
            if($currency->getCoinGeckoID() === null)            
            {
                if($coinGeckoData === null)
                {
                    $coinGeckoData = $this->coinGecko->getApiCoinsList();
                }
            }
            $symbol = strtolower($currency->getSymbol());
            foreach($coinGeckoData as $coinData)
            {
                if($coinData['symbol'] == $symbol)
                {
                    $currency->setCoinGeckoID($coinData['id']);
                    $this->currencyRepo->saveEntity($currency);
                    $updates++;
                }
            }
        }
        return $updates;
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

}