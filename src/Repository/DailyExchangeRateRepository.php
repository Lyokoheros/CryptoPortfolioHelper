<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\DailyExchangeRate;
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
            if($exchangeRateData['date'] instanceof Stringable || is_string($exchangeRateData['date']))
            {
                $exchangeRateData['date'] = (new \DateTime($exchangeRateData['date']))->format('Y-m-d');
            }
            if(!$exchangeRateData['date'] instanceof \DateTimeInterface)
            {
                throw new \RuntimeException('Wrong date format');
            }
            $dailyExchangeRate->setDate($exchangeRateData['date']);
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

    public function addExchangeRateIfNotExists($exchangeRateData): void
     {
        $criteria = [];
        foreach(['baseCurrency', 'exchangedCurrency', 'date'] as $field)
        {
            if(isset($exchangeRateData[$field]))
            {   
                $criteria[$field] = $exchangeRateData[$field];
            }
            else
            {
                throw new \RuntimeException("$field is required to add exchange rate");
            }
        }
        $existingRate = $this->findOneBy($criteria);

        if (!$existingRate) 
        {
            $this->addExchangeRate($exchangeRateData);
        }
     }
}
