<?php

namespace App\Parser;

use App\Entity\Currency;
use App\Entity\Exchange;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use App\Entity\User;
use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRepository;
use App\Repository\PortfolioRepository;
use App\Repository\TransactionBatchRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use League\Csv\Reader;

abstract class AbstractExchangeParser
{
    public function __construct(
        protected UserRepository $userRepo,
        protected ExchangeRepository $exchangeRepo,
        protected PortfolioRepository $portfolioRepo,
        protected TransactionBatchRepository $transactionBatchRepo,
        protected TransactionRepository $transactionRepo,
        protected CurrencyRepository $currencyRepo
    ) {
    }

    public abstract function parseTransactionsCSVData($data, User $user): void;

    protected abstract function getExchange(): Exchange;

    protected function parseCSV(string $csvContent): array
    {
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0); // First row as headers
        
        $headers = $csv->getHeader();
        $headers = array_map('trim', $headers);
        
        $data = [];
        foreach ($csv->getRecords() as $lineNumber => $record) {
            $record = array_map('trim', $record);
            
            if (count($record) !== count($headers)) {
                throw new \InvalidArgumentException(
                    "Line " . ($lineNumber + 2) . " has " . count($record) . 
                    " columns but expected " . count($headers)
                );
            }
            
            $row = array_combine($headers, $record);
            $data[] = $row;
        }
        
        if (empty($data)) {
            throw new \InvalidArgumentException('CSV file contains no data rows');
        }
        
        return $data;
    }

    protected function splitValueAndCurrency(string $value): array
    {
        preg_match('/^(\d+\.?\d*)([A-Za-z][A-Za-z0-9]*)$/', $value, $matches);
        
        if (empty($matches)) {
            throw new \InvalidArgumentException("Invalid value format: '$value'");
        }
        
        return [
            'value' => $matches[1],
            'currency' => $matches[2]
        ];
    }
}