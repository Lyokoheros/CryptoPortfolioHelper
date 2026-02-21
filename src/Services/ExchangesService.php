<?php

namespace App\Services;

use App\Entity\Currency;
use App\Entity\Exchange;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use App\Entity\User;
use App\Repository\ExchangeRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExchangesService
{

    public function __construct(
        private ExchangeRepository $exchangeRepo, 
        private readonly ContainerInterface $container
    ) {}

    public function addCSVDataFromExchage(string $exchangeName, User $user, string $csvData)
    {
        $exchange = $this->exchangeRepo->findOneBy(['name' => $exchangeName]);
        
        $parserClass = $exchange->getParserClass();

        if (!class_exists($parserClass)) 
        {
            throw new \InvalidArgumentException("Parser class '$parserClass' does not exist");
        }
    
        if (!$this->container->has($parserClass))
        {
            throw new \InvalidArgumentException("Parser class '$parserClass' is not registered as a service");
        }
    
        $parser = $this->container->get($parserClass);
        $parser->parseTransactionsCSVData($csvData, $user);         
    }

}