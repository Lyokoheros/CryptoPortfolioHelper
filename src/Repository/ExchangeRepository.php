<?php

namespace App\Repository;

use App\Entity\Exchange;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<Exchange>
 */
class ExchangeRepository extends EnhancedEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    
    public function addExchange($exchangeData): void
    {
        $exchange = new Exchange();

        $this->entityManager->persist($exchange);

        $this->editExchange(
            $exchange->getId(), 
            $exchangeData,
            $exchange
        );
    }
    
    public function editExchange($exchangeId, $exchangeData, ?Exchange $exchange = null): void
    {
        if($exchange === null)
        {
            $exchange = $this->find($exchangeId);
            if(!$exchange)
            {
                throw new \RuntimeException('Exchange not found');
            }
        }
        if(isset($exchangeData['parserClass']))
        {
            $parserClass = $exchangeData['parserClass'];
    
            if (!str_starts_with($parserClass, 'App\\Parser\\')) 
            {
                $exchangeData['parserClass'] = 'App\\Parser\\' . $parserClass;
            }
        }
        
        $this->editEntity($exchange, $exchangeData);        
    }
}
