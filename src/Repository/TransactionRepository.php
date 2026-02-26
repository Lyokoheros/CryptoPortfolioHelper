<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\Exchange;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<Transaction>
 */
class TransactionRepository extends EnhancedEntityRepository
{
    private $batchRepository;
    private $currencyRepository;
    private $exchangeRepository;
    private ?DateTime $lastCacheRefresh = null;
    private string $updateFrequency = '10 minutes';
    private array $assetCache = [];
    //Structure: $assetCache[$type][$portoflioId][$criteriaKey][$symbol]
    //available types: ['income','expenses']
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
        $this->batchRepository = $this->entityManager->getRepository(TransactionBatch::class);
        $this->currencyRepository = $this->entityManager->getRepository(Currency::class);
        $this->exchangeRepository = $this->entityManager->getRepository(Exchange::class);
    }

    public function addTransaction($transactionData): void
    {
        $transaction = new Transaction();

        if(!isset($transactionData['batchId']) && !isset($transactionData['transactionBatch']))
        {
            throw new \RuntimeException('Transaction must be part of a batch (batchId missing)');
        }

        if(!$transactionData['boughtCurrencyId'] && !$transactionData['boughtCurrencySymbol'])
        {
            throw new \RuntimeException('Transaction must have a bought currency (boughtCurrencyId or boughtCurrencySymbol missing)');
        }
        if(!$transactionData['soldCurrencyId'] && !$transactionData['soldCurrencySymbol'])
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

        if(isset($transactionData['batchId']))
        {
            $batch = $this->batchRepository->find($transactionData['batchId']);
            $transaction->setTransactionBatch($batch);
        }

        if(isset($transactionData['boughtCurrencyId']))
        {
            $boughtCurrency = $this->currencyRepository->find(
                $transactionData['boughtCurrencyId']
            );
            $transaction->setBoughtCurrency($boughtCurrency);
        }
        else if(isset($transactionData['boughtCurrencySymbol']))
        {
            $boughtCurrency = $this->currencyRepository->findOneBy([
                'symbol' => $transactionData['boughtCurrencySymbol']
            ]);           
            $transaction->setBoughtCurrency($boughtCurrency);
        }

        if(isset($transactionData['soldCurrencyId']))
        {
            $soldCurrency = $this->currencyRepository->find(
                $transactionData['soldCurrencyId']
            );
            $transaction->setSoldCurrency($soldCurrency);
        }
        else if(isset($transactionData['soldCurrencySymbol']))
        {
            $soldCurrency = $this->currencyRepository->findOneBy([
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

    private function getCriteriaKey(array $optionalCriteria): string
    {
        // Create unique key based on criteria
        $criteriaKey = md5(json_encode($optionalCriteria));
        return $criteriaKey;
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
                $incomes = $this->assetCache['income'][$portoflioId][$criteriaKey][$symbol];
            }
            else
            {
                $incomes = $this->getAllAssetsIncome(
                    $portfolio, 
                    $optionalCriteria
                );
            }
            $incomeTotal +=  $incomes[$symbol]['totalIncome'] 
            - $incomes[$symbol]['feesInAsset'];  
        }       

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
                $expenses = $this->getAllAssetsIncome(
                    $portfolio, 
                    $optionalCriteria
                );
            }
            
            $expensesTotal +=  $expenses[$symbol]['totalIncome'] 
            - $expenses[$symbol]['feesInAsset'];  
        }       

        return $expensesTotal;
    }

    public function getAssetsIncomeInPortfolio(Portfolio $portfolio, $optionalCriteria = []): array
    {
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        $portoflioId = $portfolio->getId();
        if(isset($this->assetCache['expenses'][$portoflioId][$criteriaKey]))
        {
            return $this->assetCache['expenses'][$portoflioId][$criteriaKey];
        }

        $qb = $this->createQueryBuilder('t')
            ->select('
                t.boughtCurrency as asset,
                SUM(t.buyValue) as totalIncome,
                SUM(CASE 
                    WHEN t.feeCurrency = t.boughtCurrency
                    THEN t.fee 
                    ELSE 0 
                END) as feesInAsset
            ')
            ->leftJoin('t.transactionBatch', 'tb')
            ->where('tb.portfolio = :portfolio')
            ->andWhere('t.boughtCurrency IS NOT NULL')
            ->setParameter('portfolio', $portfolio)
            ->groupBy('t.boughtCurrency');

        foreach ($optionalCriteria as $field => $value)
        {
            $qb->andWhere("t.$field = :$field")
            ->setParameter($field, $value);
        }

        $results = $qb->getQuery()->getResult();
        
        // Index by symbol
        $indexed = [];
        foreach ($results as $row)
        {
            $symbol = $row['asset']->getSymbol();
            $indexed[$symbol] = [
                'totalIncome' => $row['totalIncome'] ?? 0,
                'feesInAsset' => $row['feesInAsset'] ?? 0,
            ];
        }
        $this->assetCache['income'][$portoflioId][$criteriaKey] = $indexed;
        
        return $indexed;
    }


    public function getAssetsExpensesInPortfolio(Portfolio $portfolio, $optionalCriteria = []): array
    {
        $criteriaKey = $this->getCriteriaKey($optionalCriteria);
        $this->checkAssetCache();
        if(isset($this->assetCache['expenses'][$portfolio->getId()][$criteriaKey]))
        {
            return $this->assetCache['expenses'][$portfolio->getId()][$criteriaKey];
        }

        $qb = $this->createQueryBuilder('t')
            ->select('
                t.soldCurrency as asset,
                SUM(t.sellValue) as totalExpenses,
                SUM(CASE 
                    WHEN t.feeCurrency = t.boughtCurrency
                    THEN t.fee 
                    ELSE 0 
                END) as feesInAsset
            ')
            ->leftJoin('t.transactionBatch', 'tb')
            ->where('tb.portfolio = :portfolio')
            ->andWhere('t.soldCurrency IS NOT NULL')
            ->setParameter('portfolio', $portfolio)
            ->groupBy('t.boughtCurrency');

        foreach ($optionalCriteria as $field => $value)
        {
            $qb->andWhere("t.$field = :$field")
            ->setParameter($field, $value);
        }

        $results = $qb->getQuery()->getResult();
        
        // Index by symbol
        $indexed = [];
        foreach ($results as $row)
        {
            $symbol = $row['asset']->getSymbol();
            $indexed[$symbol] = [
                'totalExpenses' => $row['totalExpenses'] ?? 0,
                'feesInAsset' => $row['feesInAsset'] ?? 0,
            ];

        }
        $this->assetCache['expenses'][$portfolio->getId()][$this->getCriteriaKey($optionalCriteria)] = $indexed;
        
        return $indexed;
    }
}
