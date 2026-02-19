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
use League\Csv\Reader;

abstract class AbstractExchangeParser
{
    public function __construct(
        protected ExchangeRepository $exchangeRepo,
        protected PortfolioRepository $portfolioRepo,
        protected TransactionBatchRepository $transactionBatchRepo,
        protected TransactionRepository $transactionRepo,
        protected CurrencyRepository $currencyRepo
    ) {
    }

    public abstract function parseTransactionCSVData($data, User $user): void;

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
}