<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use App\Service\CryptoApi\CryptoApiProviderInterface;

class CurrencyService
{   
    public function __construct(
        private CurrencyRepository $currencyRepo,
        private CryptoApiProviderInterface $cryptoApi

    ) {}

    public function updatePrice(Currency $currency): float
    {
        $now = strtotime(date("Y-m-d H:i:s"));
        $limit = date("Y-m-d H:i:s", $now - (15 * 60));
        if($currency->getLastPriceUpdate() <  $limit)
        {
            $price = $this->cryptoApi->getCryptoPrice($currency);
            $currency->setLastPriceUpdate(new \DateTime());
        }
        $currency->setCurrentPrice($price);
        

        $this->currencyRepo->saveEntity($currency);
        return $price;
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

}