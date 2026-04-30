<?php

namespace App\Parser;

use App\Entity\Exchange;
use App\Entity\Transaction;
use App\Entity\User;
use DateTime;

class BinanceParser extends AbstractExchangeParser implements ExchangeParserInterface
{
    protected function getExchange(): Exchange
    {
        return $this->exchangeRepo->findoneBy(['name' => 'Binance']);
    }

    public function parseTransactionsCSVData($csvData, User $user): void
    {
        $exchange = $this->getExchange();
        $transactionsData = $this->parseCSV($csvData);
        $defaultPortfolio = $this->userRepo->findUsersDefaultPortfolio($user);

        $defualtBatchName = "Purchase";
        $portfolio = null;
        $portfolioOrdinalNumbers = [];
        foreach($transactionsData as $transactionData)
        {
            
            if(isset($transactionData['portfolio']))
            {
                $portfolio = $this->portfolioRepo->findOrCreatePortfolio(
                    $transactionData['portfolio'],
                    ['user' => $user]
                );
            }
            $portfolio ??= $defaultPortfolio ?? $this->portfolioRepo->addPortfolio([
                'name' => 'Default Portfolio',
                'isDefault' => true,
                'user' => $user
            ]);

            $portfolioOrdinalNumbers[$portfolio->getName()] ??= 1;

            
            $batch = $this->transactionBatchRepo->findOrCreateBatch(
                $transactionData['batch']
                    ?: ($defualtBatchName . $portfolioOrdinalNumbers[$portfolio->getName()]),
                [
                    'type' => $portfolio->getDefualtBatchType(),
                    'finished' => false,
                    'portfolio' => $portfolio,
                    'date' => $transactionData['Date(UTC)'] ?? null,
                    'ordinalNumber' => $portfolioOrdinalNumbers[$portfolio->getName()]
                ]
            );
            $portfolioOrdinalNumbers[$portfolio->getName()]++;

            $executed = $this->splitValueAndCurrency($transactionData['Executed']);
            $amount = $this->splitValueAndCurrency($transactionData['Amount']);
            $fee = $this->splitValueAndCurrency($transactionData['Fee']);

            if($transactionData['Side'] == 'BUY')
            {

                $boughtCurrency = $this->currencyRepo->findOrCreateCurrency($executed['currency'],
                    [
                        'name' => $executed['currency'],
                        'currentPrice' => $transactionData['Price'],
                        'lastPriceUpdate' => $transactionData['Date(UTC)']
                    ]
                );
                $buyValue = $executed['value'];

                $soldCurrency = $this->currencyRepo->findOrCreateCurrency($amount['currency'], 
                    [
                        'name' => $amount['currency'],
                    ]
                );
                $sellValue = $amount['value'];

            }
            else if($transactionData['Side'] == 'SELL')
            {
                $boughtCurrency = $this->currencyRepo->findOrCreateCurrency($amount['currency'],
                    [
                        'name' => $amount['currency']
                    ]
                );
                $buyValue = $amount['value'];

                $soldCurrency = $this->currencyRepo->findOrCreateCurrency($executed['currency'], 
                    [
                        'name' => $executed['currency'],
                        'currentPrice' => $transactionData['Price'],
                        'lastPriceUpdate' => $transactionData['Date(UTC)']
                    ]
                );
                $sellValue = $executed['value'];
                
            }
            $feeCurrency = $this->currencyRepo->findOrCreateCurrency(
                $fee['currency'], 
                ['name' => $fee['currency']]
            );


           

            $transaction = new Transaction();
            $this->transactionRepo->editEntity($transaction, [
                'transactionBatch' => $batch,
                'BoughtCurrency' => $boughtCurrency,
                'BuyValue' => $buyValue,
                'SoldCurrency' => $soldCurrency,
                'SellValue' => $sellValue,
                'FeeCurrency' => $feeCurrency,
                'Fee' => $fee['value'],
                'Exchange' => $exchange,
                'MarketPrice' => $transactionData['Price'],
                'Date' => $this->getDate($transactionData)
            ]);

            $transactionCountInBatch = count($this->transactionRepo
                ->findBy(['transactionBatch' => $batch]));
                
            if($transactionCountInBatch == $portfolio->getBatchSize())
            {
                $this->transactionBatchRepo->editTransactionBatch($batch, ['isFinished' => true]);
            }
        }
    }

    private function getDate(array $transactionData): ?DateTime
    {
        if(isset($transactionData['Date(UTC)']))
        {
            return new DateTime($transactionData['Date(UTC)']);
        }
        if(isset($transactionData['Date']))
        {
            return new DateTime($transactionData['Date']);
        }
        if(isset($transactionData['Time']))
        {
            return new DateTime($transactionData['Time']);
        }
        if(isset($transactionData['timestamp']))
        {
            return new DateTime('@' . $transactionData['timestamp']);
        }
        return null;
    }

}