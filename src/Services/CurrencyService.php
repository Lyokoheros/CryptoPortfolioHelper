<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
//use App\Entity\DailyExchangeRate;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class CurrencyService
{
    private CurrencyRepository $currencyRepo;
    private $dailyExchangeRateRepo;

    public function __construct(ManagerRegistry $registry)
    {
        $this->currencyRepo = $registry->getManager()->getRepository(Currency::class);
        //$this->dailyExchangeRateRepo = $registry->getManager()->getRepository(DailyExchangeRate::class);

    }

    public function updatePrice(Currency $currency): void
    {
        //to implement(call external API, update price in database)
        $currency->setLastPriceUpdate(new \DateTime());
        $this->currencyRepo->entityManager->persist($currency);
        $this->currencyRepo->entityManager->flush();
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