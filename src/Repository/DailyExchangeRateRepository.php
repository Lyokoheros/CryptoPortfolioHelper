<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\DailyExchangeRate;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<DailyExchangeRate>
 */
class DailyExchangeRateRepository extends EnhancedEntityRepository
{
    private $currencyRepository;
    
    public function __construct(ManagerRegistry $registry)
    {
        $this->currencyRepository = $registry->getManager()->getRepository(Currency::class);
        parent::__construct($registry);
    }

    public function addExchangeRate($exchangeRateData): void
    {
        $dailyExchangeRate = new DailyExchangeRate();

        if(isset($exchangeRateData['baseCurrencyId']))
        {
            $baseCurrency = $this->currencyRepository->find($exchangeRateData['baseCurrencyId']);
            if(!$baseCurrency)
            {
               throw new \RuntimeException('Base currency not found');
            }
            $dailyExchangeRate->setBaseCurrency($baseCurrency);
        } 
        else
        {
            if(isset($exchangeRateData['baseCurrencySymbol']))
            {
                $baseCurrency = $this->entityManager->getRepository(Currency::class)->findOneBy(['symbol' => $exchangeRateData['baseCurrencySymbol']]);
                if(!$baseCurrency)
                {
                    throw new \RuntimeException('Base currency not found by symbol');
                }
                $dailyExchangeRate->setBaseCurrency($baseCurrency);
            }
            else
            {
                throw new \RuntimeException('Base currency id or symbol is required');
            }
        }

        if(isset($exchangeRateData['exchangedCurrencyId']))
        {
            $exchangedCurrency = $this->currencyRepository->find($exchangeRateData['exchangedCurrencyId']);
            if(!$exchangedCurrency)
            {
               throw new \RuntimeException('Exchanged currency not found');
            }
            $dailyExchangeRate->setExchangedCurrency($exchangedCurrency);
        } 
        else
        {
            if(isset($exchangeRateData['exchangedCurrencySymbol']))
            {
                $exchangedCurrency = $this->entityManager->getRepository(Currency::class)->findOneBy(['symbol' => $exchangeRateData['exchangedCurrencySymbol']]);
                if(!$exchangedCurrency)
                {
                    throw new \RuntimeException('Exchanged currency not found by symbol');
                }
                $dailyExchangeRate->setExchangedCurrency($exchangedCurrency);
            }
            else
            {
                throw new \RuntimeException('Exchanged currency id or symbol is required');
            }
        }

        if(isset($exchangeRateData['date']))
        {
            $dailyExchangeRate->setDate(new \DateTime($exchangeRateData['date']));
        }
        else
        {
            throw new \RuntimeException('Date is required');
        }



        $this->entityManager->persist($dailyExchangeRate);

        $this->editExchangeRate(
            $dailyExchangeRate->getId(), 
            $exchangeRateData,
            $dailyExchangeRate
        );
    }
    
    public function editExchangeRate($exchangeRateId, $exchangeRateData, ?DailyExchangeRate $exchangeRate = null): void
    {
        if($exchangeRate === null)
        {
            $exchangeRate = $this->find($exchangeRateId);
            if(!$exchangeRate)
            {
                throw new \RuntimeException('Exchange rate not found');
            }
        }


        if(isset($exchangeRateData['baseCurrencyId']))
        {
            $exchangeRateData['baseCurrency'] = $this->currencyRepository->find($exchangeRateData['baseCurrencyId']);
        } 
        else if(isset($exchangeRateData['baseCurrencySymbol']))
        {
            $exchangeRateData['baseCurrency'] = $this->entityManager->getRepository(Currency::class)->findOneBy(['symbol' => $exchangeRateData['baseCurrencySymbol']]);
        }        

        if(isset($exchangeRateData['exchangedCurrencyId']))
        {
            $exchangeRateData['exchangedCurrency'] = $this->currencyRepository->find($exchangeRateData['exchangedCurrencyId']);
        } 
        else if(isset($exchangeRateData['exchangedCurrencySymbol']))
        {
            $exchangeRateData['exchangedCurrency'] = $this->entityManager->getRepository(Currency::class)->findOneBy(['symbol' => $exchangeRateData['exchangedCurrencySymbol']]);
        }

        if(isset($exchangeRateData['date']))
        {
            $exchangeRateData['date'] = new \DateTime($exchangeRateData['date']);
        }
        
        $this->editEntity($exchangeRate, $exchangeRateData);        
    }
}
