<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\Exchange;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends EnhancedEntityRepository<Transaction>
 */
class TransactionRepository extends EnhancedEntityRepository
{
    private ?DateTime $lastCacheRefresh = null;
    private string $updateFrequency = '20 minutes';
    private array $assetCache = [];
    //Structure: $assetCache[$category][$portoflioId][$criteriaKey][$symbol]
    private array $cachedDataCategories = [
        'income',
        'expenses',
        'buyTransactions',
        'sellTransactions'
    ];
    private $associationFields = [
        'boughtCurrency',
        'soldCurrency',
        'feeCurrency',
        'transactionBatch'
    ];
        
        
    
    public function __construct(
        ManagerRegistry $registry,
        private TransactionBatchRepository $batchRepository,
        private CurrencyRepository $currencyRepository,
        private ExchangeRepository $exchangeRepository,
        private LoggerInterface $logger
    )
    {
        parent::__construct($registry);
        $this->associationFields = array_flip($this->associationFields);
    }

    private function logMemory(string $label): void
    {
        $usage = memory_get_usage(true) / 1024 / 1024;
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        $this->logger->info("[$label] Current: {$usage}MB | Peak: {$peak}MB");
    }

    public function addTransaction($transactionData): void
    {
        $transaction = new Transaction();

        if(!isset($transactionData['batchId']) && !isset($transactionData['transactionBatch']))
        {
            throw new \RuntimeException('Transaction must be part of a batch (batchId missing)');
        }

        if(!$transactionData['boughtCurrencyId'] && !$transactionData['boughtCurrencySymbol'] && !$transactionData['boughtCurrency'])
        {
            throw new \RuntimeException('Transaction must have a bought currency (boughtCurrencyId or boughtCurrencySymbol missing)');
        }
        if(!$transactionData['soldCurrencyId'] && !$transactionData['soldCurrencySymbol'] && !$transactionData['soldCurrency'])
        {
            throw new \RuntimeException('Transaction must have a sold currency (soldCurrencyId or soldCurrencySymbol missing)');
        }
        if(!$transactionData['exchangeId'] && !$transactionData['exchangeName'])
        {
            throw new \RuntimeException('Transaction must have an exchange (exchangeId or exchangeName missing)');
        }


        $transactionData['date'] = $transactionData['date'] ?? '';        

        $this->entityManager->persist($transaction);

        $this->editTransaction(
            $transaction->getId(), 
            $transactionData,
            $transaction
        );
    }   


    public function editTransaction(
        $id, $transactionData,
        $transaction = null
    ): void {
        if($transaction === null)
        {
            $transaction = $this->find($id);
        }

        $batch = $transactionData['transactionBatch'] ?? null;
        $boughtCurrency = $transactionData['boughtCurrency'] ?? null;
        $soldCurrency = $transactionData['soldCurrency'] ?? null;

        if(isset($transactionData['batchId']))
        {
            $batch ??= $this->batchRepository->find($transactionData['batchId']);
            $transaction->setTransactionBatch($batch);
        }

        if(isset($transactionData['boughtCurrencyId']))
        {
            $boughtCurrency ??= $this->currencyRepository->find(
                $transactionData['boughtCurrencyId']
            );
            $transaction->setBoughtCurrency($boughtCurrency);
        }
        else if(isset($transactionData['boughtCurrencySymbol']))
        {
            $boughtCurrency ??= $this->currencyRepository->findOneBy([
                'symbol' => $transactionData['boughtCurrencySymbol']
            ]);           
            $transaction->setBoughtCurrency($boughtCurrency);
        }

        if(isset($transactionData['soldCurrencyId']))
        {
            $soldCurrency ??= $this->currencyRepository->find(
                $transactionData['soldCurrencyId']
            );
            $transaction->setSoldCurrency($soldCurrency);
        }
        else if(isset($transactionData['soldCurrencySymbol']))
        {
            $soldCurrency ??= $this->currencyRepository->findOneBy([
                'symbol' => $transactionData['soldCurrencySymbol']
            ]);           
            $transaction->setSoldCurrency($soldCurrency);
        }        

        if(isset($transactionData['feeCurrencyId']))
        {
            $feeCurrency = $this->currencyRepository->find(
                $transactionData['feeCurrencyId']
            );
            $transaction->setFeeCurrency($feeCurrency);
        }
        else if(isset($transactionData['feeCurrencySymbol']))
        {
            $feeCurrency = $this->currencyRepository->findOneBy([
                'symbol' => $transactionData['feeCurrencySymbol']
            ]);           
            $transaction->setFeeCurrency($feeCurrency);
        }

        if(isset($transactionData['exchangeId']))
        {
            $exchange = $this->exchangeRepository->find(
                $transactionData['exchangeId']
            );
            $transaction->setExchange($exchange);
        }
        else if(isset($transactionData['exchangeName']))
        {
            $exchange = $this->exchangeRepository->findOneBy([
                'name' => $transactionData['exchangeName']
            ]);           
            $transaction->setExchange($exchange);
        }

        if(isset($transactionData['date']))
        {
            $transactionData['date'] = new \DateTime($transactionData['date']);
        }


        $portfolio = $batch->getPortfolio();
        $portfolio->addSoldAsset($soldCurrency);
        $portfolio->addBoughtAsset($boughtCurrency);
        $this->saveEntity($portfolio);

        $this->editEntity($transaction, $transactionData);
    }

    public function checkAssetCache(): void
    { 
        $now = new DateTime();
        $dateLimit = (clone $now)->modify('-' . $this->updateFrequency);

        if ($this->lastCacheRefresh === null || $this->lastCacheRefresh < $dateLimit) {
            $this->assetCache = [];
            $this->lastCacheRefresh = $now;
        }
    }

    public function resetPortfolioCache(Portfolio $portfolio): void
    {
        $portfolioId = $portfolio->getId();
        foreach($this->cachedDataCategories as $category)
        {
            $this->assetCache[$category][$portfolioId] = [];
        }
    }

    private function getCriteriaKey(array $optionalCriteria): string
    {
        // Create unique key based on criteria
        $criteriaKey = [];
        foreach($optionalCriteria as $key => $value)
        {
            $criteriaKey[] = $key ."->". $value;            
        }
        sort($criteriaKey);
        if($criteriaKey == [])
        {
            return 'none';
        }
        else
        {
            return implode('|', $criteriaKey);
        }          
    }

    /**
     * Get single asset income (uses the bulk query)
     */
    public function getAssetIncome(
        Currency $asset,
        array $portfolios,
        array $optionalCriteria = []
    ): float
    {
        $incomeTotal = 0;
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $symbol = $asset->getSymbol();

        foreach($portfolios as $portfolio)
        {
            $portoflioId = $portfolio->getId();
            $incomes = 0;
            if(isset($this->assetCache['income'][$portoflioId][$criteriaKey][$symbol]))
            {
                $incomes = $this->assetCache['income'][$portoflioId][$criteriaKey];
            }
            else
            {
                $incomes = $this->getAssetsIncomeInPortfolio(
                    $portfolio, 
                    $optionalCriteria
                );
                $this->assetCache['income'][$portoflioId][$criteriaKey] = $incomes;                
            }            
            if(isset($incomes[$symbol]))
            {
                $incomeTotal +=  $incomes[$symbol]['totalIncome']
                    - $incomes[$symbol]['feesInAsset'];
            }
        }    
        //echo $symbol ."|". $incomeTotal . "<br>\n";

        return $incomeTotal;
    }

    /**
     * Get single asset expenses (uses the bulk query)
     */
    public function getAssetExpenses(
        Currency $asset,
        array $portfolios,
        array $optionalCriteria = []
    ): float
    {
        $expensesTotal = 0;
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $symbol = $asset->getSymbol();

        foreach($portfolios as $portfolio)
        {
            $portoflioId = $portfolio->getId();
            $expenses = 0;
            if(isset($this->assetCache['expenses'][$portoflioId][$criteriaKey][$symbol]))
            {
                $expenses = $this->assetCache['expenses'][$portoflioId][$criteriaKey][$symbol];
            }
            else
            {
                $expenses = $this->getAssetsExpensesInPortfolio(
                    $portfolio, 
                    $optionalCriteria
                );
                $this->assetCache['expenses'][$portoflioId][$criteriaKey][$symbol] = $expenses;
            }
            
            if (isset($expenses[$symbol]))
            {
                $expensesTotal +=  $expenses[$symbol]['totalExpenses']
                    - $expenses[$symbol]['feesInAsset'];  
            }                
        }       

        return $expensesTotal;
    }

    public function getAssetTransactionNumber(
        Currency $asset,
        array $portfolios,
        string $type = "BUY",
        array $optionalCriteria = []
    ): float
    {
        $type = strtoupper($type);
        if($type !== "BUY" && $type !== "SELL" && $type !== "BOTH")
        {
            throw new \RuntimeException('Wrong transaction type. Must be one of: BUY, SELL or BOTH(case insensitive)');
        }
        $transactionsTotal = 0;
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $symbol = $asset->getSymbol();

        foreach($portfolios as $portfolio)
        {
            $portoflioId = $portfolio->getId();
            $transactions = 0;
            
            if($type == "BUY" || "BOTH")
            {
                if(isset($this->assetCache['buyTransactions'][$portoflioId][$criteriaKey][$symbol]))
                {
                    $transactions = $this->assetCache['buyTransactions'][$portoflioId][$criteriaKey][$symbol];
                }
                else
                {
                    $transactions = $this->getBuyTransactionCounts(
                    $portfolio, 
                    $optionalCriteria
                    );
                    $this->assetCache['buyTransactions'][$portoflioId][$criteriaKey] = $transactions;
                    $transactions = $transactions[$symbol];
                }
                $transactionsTotal += $transactions;
            }

            if($type == "SELL" || "BOTH")
            {
                if(isset($this->assetCache['sellTransactions'][$portoflioId][$criteriaKey][$symbol]))
                {
                    $transactions = $this->assetCache['sellTransactions'][$portoflioId][$criteriaKey][$symbol];
                }
                else
                {
                    $transactions = $this->getSellTransactionCounts(
                    $portfolio, 
                    $optionalCriteria
                    );
                    $this->assetCache['sellTransactions'][$portoflioId][$criteriaKey] = $transactions;
                    $transactions = $transactions[$symbol];
                }
                $transactionsTotal += $transactions;
            }
                      
        }       

        return $transactionsTotal;
    }

    public function getAssetsIncomeInPortfolio(Portfolio $portfolio, $optionalCriteria = []): array
    {
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $portfolioId = $portfolio->getId();
        $associationFields = ['boughtCurrency', 'soldCurrency', 'feeCurrency', 'transactionBatch'];
        $associationFields = array_flip($associationFields);
        if (isset($this->assetCache['income'][$portfolioId][$criteriaKey])) 
        {
            return $this->assetCache['income'][$portfolioId][$criteriaKey];
        }

        $qb = $this->createQueryBuilder('t')
            // join the currency so we can group by a real field
            ->leftJoin('t.boughtCurrency', 'bc')
            ->leftJoin('t.transactionBatch', 'tb')
            ->select('
                bc.symbol AS assetSymbol,
                SUM(t.buyValue) AS totalIncome,
                SUM(
                    CASE WHEN IDENTITY(t.feeCurrency) = IDENTITY(t.boughtCurrency)
                        THEN t.fee ELSE 0 END
                ) AS feesInAsset
            ')
            //->addSelect('t.id AS dummyID') //to satisfy parser
            ->where('tb.portfolio = :portfolio')
            ->andWhere('bc IS NOT NULL')
            ->setParameter('portfolio', $portfolio)
            ->groupBy('bc.id');

        foreach ($optionalCriteria as $field => $value) 
        {
            // if someone filters on an association, compare its id
            if (isset($this->associationFields[$field]))
            {
                $qb->andWhere("IDENTITY(t.$field) = :$field");
            }
            else
            {
                $qb->andWhere("t.$field = :$field");
            }
            $qb->setParameter($field, $value);
        }

        $results = $qb->getQuery()->getResult();

        $indexed = [];
        foreach ($results as $row) 
        {
            $symbol = $row['assetSymbol'];
            $indexed[$symbol] = [
                'totalIncome' => $row['totalIncome'] ?? 0,
                'feesInAsset' => $row['feesInAsset'] ?? 0,
            ];
        }
        //var_dump($indexed);
        $this->assetCache['income'][$portfolioId][$criteriaKey] = $indexed;

        return $indexed;
    }


    public function getAssetsExpensesInPortfolio(Portfolio $portfolio, $optionalCriteria = []): array
    {
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $portfolioId = $portfolio->getId();
        //test
        $this->logMemory("Currency Choosen:" .($optionalCriteria['boughtCurrency'] ?? "all"). " CODE: " . $criteriaKey);
        if (isset($this->assetCache['expenses'][$portfolioId][$criteriaKey])) 
        {
            return $this->assetCache['expenses'][$portfolioId][$criteriaKey];
        }

        $qb = $this->createQueryBuilder('t')
            // join the currency so we can group by a real field
            ->leftJoin('t.soldCurrency', 'sc')
            ->leftJoin('t.transactionBatch', 'tb')
            ->select('
                sc.symbol AS assetSymbol,
                SUM(t.sellValue) AS totalExpenses,
                SUM(
                    CASE WHEN IDENTITY(t.feeCurrency) = IDENTITY(t.soldCurrency)
                        THEN t.fee ELSE 0 END
                ) AS feesInAsset
            ')
            ->where('tb.portfolio = :portfolio')
            ->andWhere('sc IS NOT NULL')
            ->setParameter('portfolio', $portfolio)
            ->groupBy('sc.id');

        foreach ($optionalCriteria as $field => $value) 
        {
            // if someone filters on an association, compare its id
            if (isset($this->associationFields[$field]))
            {
                $qb->andWhere("IDENTITY(t.$field) = :$field");
            }
            else
            {
                $qb->andWhere("t.$field = :$field");
            }
            $qb->setParameter($field, $value);
        }

        $results = $qb->getQuery()->getResult();

        $indexed = [];
        foreach ($results as $row) {
            $symbol = $row['assetSymbol'];
            $indexed[$symbol] = [
                'totalExpenses' => $row['totalExpenses'] ?? 0,
                'feesInAsset' => $row['feesInAsset'] ?? 0,
            ];

        }
        $this->assetCache['expenses'][$portfolioId][$criteriaKey] = $indexed;

        return $indexed;
    }

    public function getBuyTransactionCounts(Portfolio $portfolio, $optionalCriteria = []): array
    {
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $portfolioId = $portfolio->getId();
        
        if (isset($this->assetCache['buyTransactions'][$portfolioId][$criteriaKey])) 
        {
            return $this->assetCache['buyTransactions'][$portfolioId][$criteriaKey];
        }

        $qb = $this->createQueryBuilder('t')
            // join the currency so we can group by a real field
            ->leftJoin('t.boughtCurrency', 'sc')
            ->leftJoin('t.transactionBatch', 'tb')
            ->select('
                sc.symbol AS assetSymbol,
                COUNT(t.id) AS transactionsNumber
            ')
//            ->addSelect('t.id AS dummyID') //to satisfy parser
            ->where('tb.portfolio = :portfolio')
            ->andWhere('sc IS NOT NULL')
            ->setParameter('portfolio', $portfolio)
            ->groupBy('sc.symbol');

        foreach ($optionalCriteria as $field => $value) 
        {
            // if someone filters on an association, compare its id
            if (isset($this->associationFields[$field]))
            {
                $qb->andWhere("IDENTITY(t.$field) = :$field");
            }
            else
            {
                $qb->andWhere("t.$field = :$field");
            }
            $qb->setParameter($field, $value);
        }

        $results = $qb->getQuery()->getResult();

        $indexed = [];
        $total = 0;
        foreach ($results as $row) {
            $symbol = $row['assetSymbol'];
            $indexed[$symbol] = [
                'transactions' => $row['transactionsNumber'] ?? 0
            ]; $total +=$row['transactionsNumber'] ?? 0;
        }
        $indexed['total'] = $total;

        $this->assetCache['buyTransactions'][$portfolioId][$criteriaKey] = $indexed;

        return $indexed;
    }

    public function getSellTransactionCounts(Portfolio $portfolio, $optionalCriteria = []): array
    {
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $portfolioId = $portfolio->getId();
        
        if (isset($this->assetCache['sellTransactions'][$portfolioId][$criteriaKey])) 
        {
            return $this->assetCache['selltransactions'][$portfolioId][$criteriaKey];
        }

        $qb = $this->createQueryBuilder('t')
            // join the currency so we can group by a real field
            ->leftJoin('t.soldCurrency', 'sc')
            ->leftJoin('t.transactionBatch', 'tb')
            ->select('
                sc.symbol AS assetSymbol,
                COUNT(t.id) AS transactionsNumber 
            ')
            ->where('tb.portfolio = :portfolio')
            ->andWhere('sc IS NOT NULL')
            ->setParameter('portfolio', $portfolio)
            ->groupBy('sc.symbol');

        foreach ($optionalCriteria as $field => $value) 
        {
            // if someone filters on an association, compare its id
            if (isset($this->associationFields[$field]))
            {
                $qb->andWhere("IDENTITY(t.$field) = :$field");
            }
            else
            {
                $qb->andWhere("t.$field = :$field");
            }
            $qb->setParameter($field, $value);
        }

        $results = $qb->getQuery()->getResult();

        $indexed = [];
        $total = 0;
        foreach ($results as $row) {
            $symbol = $row['assetSymbol'];
            $indexed[$symbol] = [
                'transactions' => $row['transactionsNumber'] ?? 0
            ];
            $total +=$row['transactionsNumber'] ?? 0;
        }
        $indexed['total'] = $total;

        $this->assetCache['sellTransactions'][$portfolioId][$criteriaKey] = $indexed;

        return $indexed;
    }


}
