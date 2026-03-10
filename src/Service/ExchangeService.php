<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ExchangeRepository;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ExchangeService
{
    public function __construct(
        private ExchangeRepository $exchangeRepo, 
        private ServiceLocator $parserLocator
    ) {}

    private function getParser(string $parserClass)
    {
        return $this->parserLocator->get($parserClass);
    }

    public function addCSVDataFromExchage(string $exchangeName, User $user, string $csvData)
    {
        $exchange = $this->exchangeRepo->findOneBy(['name' => $exchangeName]);
        
        $parserClass = $exchange->getParserClass();

        if (!class_exists($parserClass)) 
        {
            throw new \InvalidArgumentException("Parser class '$parserClass' does not exist");
        }
    
        try
        {
            $parser = $this->getParser($parserClass);
        } 
        catch (\Psr\Container\NotFoundExceptionInterface $e)
        {
            throw new \InvalidArgumentException("Parser class '$parserClass' is not registered as a service", 0, $e);
        }
    
        $parser->parseTransactionsCSVData($csvData, $user);         
    }

}