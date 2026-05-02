<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\DailyExchangeRate;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Stringable;

/**
 * @extends EnhancedEntityRepository<DailyExchangeRate>
 */
class DailyExchangeRateRepository extends EnhancedEntityRepository
{  
    public function __construct(
        ManagerRegistry $registry,
        private CurrencyRepository $currencyRepository
    ) {
        parent::__construct($registry);
    }

    public function addExchangeRate($exchangeRateData): void
    {
        $dailyExchangeRate = new DailyExchangeRate();

        if(isset($exchangeRateData['baseCurrencySymbol']))
        {
            $baseCurrency = $this->entityManager->getRepository(Currency::class)->findOneBy(['symbol' => $exchangeRateData['baseCurrencySymbol']]);
        }
        if(isset($exchangeRateData['baseCurrencyId']))
        {
            $baseCurrency = $this->currencyRepository->find($exchangeRateData['baseCurrencyId']);
        } 
        $exchangeRateData['baseCurrency'] ??= $baseCurrency ?? null;
            
        if(!isset($exchangeRateData['baseCurrency']))
        {
            throw new \RuntimeException('Base currency (can be id or symbol) is required');
        }


        if(isset($exchangeRateData['exchangedCurrencySymbol']))
        {
            $exchangedCurrency = $this->entityManager->getRepository(Currency::class)->findOneBy(['symbol' => $exchangeRateData['exchangedCurrencySymbol']]);       
        }
        if(isset($exchangeRateData['exchangedCurrencyId']))
        {
            $exchangedCurrency = $this->currencyRepository->find($exchangeRateData['exchangedCurrencyId']);
        }
        $exchangeRateData['exchangedCurrency'] ??= $exchangedCurrency ?? null;

        if(!isset($exchangeRateData['exchangedCurrency']))
        {
            throw new \RuntimeException('Exchanged currency (can be id or symbol) is required');
        }

        $dailyExchangeRate->setBaseCurrency($exchangeRateData['baseCurrency']);
        $dailyExchangeRate->setExchangedCurrency($exchangeRateData['exchangedCurrency']);


        if(isset($exchangeRateData['date']))
        {
            $dailyExchangeRate->setDate($this->handleDate($exchangeRateData['date']));
        }
        else
        {
            throw new \RuntimeException('Date is required');
        }

        if(isset($exchangeRateData['exchangeRate']))
        {
            $dailyExchangeRate->setExchangeRate($exchangeRateData['exchangeRate']);
        }
        else
        {
            throw new \RuntimeException('Exchange rate value is required');
        }

        $this->saveEntity($dailyExchangeRate);

        $this->editExchangeRate(
            $dailyExchangeRate->getId(), 
            $exchangeRateData,
            $dailyExchangeRate
        );
    }
    
    public function editExchangeRate(int $exchangeRateId, array $exchangeRateData, ?DailyExchangeRate $exchangeRate = null): void
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
            $exchangeRateData['baseCurrency'] = $this->currencyRepository->findOneBy(['symbol' => $exchangeRateData['baseCurrencySymbol']]);
        }        

        if(isset($exchangeRateData['exchangedCurrencyId']))
        {
            $exchangeRateData['exchangedCurrency'] = $this->currencyRepository->find($exchangeRateData['exchangedCurrencyId']);
        } 
        else if(isset($exchangeRateData['exchangedCurrencySymbol']))
        {
            $exchangeRateData['exchangedCurrency'] = $this->currencyRepository->findOneBy(['symbol' => $exchangeRateData['exchangedCurrencySymbol']]);
        }
        
        $this->editEntity($exchangeRate, $exchangeRateData);        
    }

    public function addExchangeRateIfNotExists($exchangeRateData): void
     {
        $criteria = [];
        foreach(['baseCurrency', 'exchangedCurrency', 'date'] as $field)
        {
            if(isset($exchangeRateData[$field]))
            {   
                $criteria[$field] = $exchangeRateData[$field];
                if($field == 'date')
                {
                    $criteria[$field] = $this->handleDate($criteria[$field]);
                }
            }
            else
            {
                throw new \RuntimeException("$field is required to add exchange rate");
            }
        }
        $existingRate = $this->findOneBy($criteria);

        if ($existingRate === null) 
        {
            $this->addExchangeRate($exchangeRateData);
        }
     }
}
