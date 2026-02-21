<?php

namespace App\Repository;

use App\Entity\Currency;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<Currency>
 */
class CurrencyRepository extends EnhancedEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    public function addCurrency($currencyData): Currency
    {
        $currency = new Currency();

        $this->entityManager->persist($currency);

        $this->editCurrency(
            $currency->getId(), 
            $currencyData,
            $currency
        );
        return $currency;
    }
    
    public function editCurrency($currencyId, $currencyData, ?Currency $currency = null): void
    {
        if($currency === null)
        {
            $currency = $this->find($currencyId);
            if(!$currency)
            {
                throw new \RuntimeException('Currency not found');
            }
        }

        if(isset($currencyData['priceCurrencyId']))
        {
            $priceCurrency = $this->find($currencyData['priceCurrencyId']);
            if(!$priceCurrency)
            {
               throw new \RuntimeException('Price currency not found');
            }
            $currencyData['pricesCurrency'] = $priceCurrency;
        }
        if(isset($currencyData['lastPriceUpdate']))
        {
            $currencyData['lastPriceUpdate'] = new \DateTime($currencyData['lastPriceUpdate']);
        }
        
        $this->editEntity($currency, $currencyData);        
    }

    public function findOrCreateCurrency($symbol, $currencyData): Currency
    {
        return $this->findOneBy(['symbol' => $symbol]) ?? $this->addCurrency([
            ...['symbol' => $symbol],
            ...$currencyData
        ]);
    }

}
