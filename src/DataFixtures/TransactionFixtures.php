<?php

namespace App\DataFixtures;

use App\Entity\Currency;
use App\Entity\Exchange;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    const TRANSACTION_BATCHES_DATA = [
        [
            'user' => 'Lyokoheros',
            'data'=> [
                [
                    'portfolio' => 'DCA portfolio',
                    'batches' => [
                        [
                            'name' => 'Week 1',
                            'ordinalNumber' => 1,
                            'type' => 'weekly',
                            'date' => '2021-07-05',
                            'finished' => true
                        ],
                        [
                            'name' => 'Week 2',
                            'ordinalNumber' => 2,
                            'type' => 'weekly',
                            'date' => '2021-07-12',
                            'finished' => true
                        ]
                    ]                        
                ]
            ]
        ]
    ];

    const TRANSACTION_DATA = [
        [
            'user' => 'Lyokoheros',
            'portfolio' => 'DCA portfolio',
            'transactionBatches' => [
                [
                    'batchName' => 'Week 1',
                    'transactions'=> 
                    [
                        [ 
                            'exchange' => 'Coinbase',
                            'boughtCurrency' => 'ICP',
                            'buyValue' => 0.2508,
                            'sellCurrency' => 'EUR',
                            'sellValue' => 10,
                            'feeCurrency' => null,
                            'fee' => 0,
                            'marketPrice' => null,
                            'date' => '2021-07-05'
                        ],
                        [
                            'exchange' => 'Coinbase',
                            'boughtCurrency' => 'ETH',
                            'buyValue' => 0.0051049,
                            'sellCurrency' => 'EUR',
                            'sellValue' => 10,
                            'feeCurrency' => null,
                            'fee' => 0,
                            'marketPrice' => null,
                            'date' => '2021-07-05'
                        ],
                        [
                            'exchange' => 'Coinbase',
                            'boughtCurrency' => 'BTC',
                            'buyValue' => 0.00033585,
                            'sellCurrency' => 'EUR',
                            'sellValue' => 10,
                            'feeCurrency' => null,
                            'fee' => 0,
                            'marketPrice' => null,
                            'date' => '2021-07-05'
                        ]                                               
                    ]                  
                ],
                [
                    'batchName' => 'Week 2',
                    'transactions'=> 
                    [
                        [ 
                            'exchange' => 'Coinbase',
                            'boughtCurrency' => 'LINK',
                            'buyValue' => 0.58129996,
                            'sellCurrency' => 'EUR',
                            'sellValue' => 10,
                            'feeCurrency' => null,
                            'fee' => '0',
                            'marketPrice' => null,
                            'date' => '2021-07-12'
                        ],
                        [
                            'exchange' => 'Coinbase',
                            'boughtCurrency' => 'ETH',
                            'buyValue' => 0.0055727,
                            'sellCurrency' => 'EUR',
                            'sellValue' => 10,
                            'feeCurrency' => null,
                            'fee' => 0,
                            'marketPrice' => null,
                            'date' => '2021-07-12'
                        ],
                        [
                            'exchange' => 'Coinbase',
                            'boughtCurrency' => 'BTC',
                            'buyValue' => 0.00034744,
                            'sellCurrency' => 'EUR',
                            'sellValue' => 10,
                            'feeCurrency' => null,
                            'fee' => 0,
                            'marketPrice' => null,
                            'date' => '2021-07-12'
                        ]                                               
                    ]                  
                ]
            ]
        ]
    ];

    private $userBatches = [];


    public function getDependencies(): array
    {
        return [
            PortfolioFixtures::class,
            CurrencyFixtures::class,
            ExchangeFixtures::class
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadTransactionBatches($manager);
        $this->loadTransaction($manager);

        $manager->flush();
    }

    public function loadTransactionBatches(ObjectManager $manager): void
    {
        $portfolioRepo = $manager->getRepository(Portfolio::class);
        $userRepo = $manager->getRepository(User::class);

        $batches = [];
        foreach(self::TRANSACTION_BATCHES_DATA as $userBatchesData)
        {
            $user = $userRepo->findOneBy(['userName' => $userBatchesData['user']]);
            
    //        echo $user->getUserName() ?? "User {$userBatchesData['user']} not found";
            foreach($userBatchesData['data'] as $portfolioBatches)
            {
                $portfolio = $portfolioRepo->findOneBy([
                    'user' => $user,
                    'name' => $portfolioBatches['portfolio']
                ]);
                //var_dump($portfolios);
  //              echo $portfolio->getName() ?? "Portfolio {$portfolioBatches['portfolio']} not found";

                $batches = [];
                foreach($portfolioBatches['batches'] as $batchData)
                {
                    if($batchData == null) {continue;}
                    $transactionBatch = new TransactionBatch();
                    $transactionBatch->setPortfolio($portfolio);
                    $transactionBatch->setName($batchData['name']);
                    $transactionBatch->setType($batchData['type']);
                    $transactionBatch->setOrdinalNumber($batchData['ordinalNumber']);
                    $transactionBatch->setDate(\DateTime::createFromFormat('Y-m-d', $batchData['date']));
                    $transactionBatch->setFinished($batchData['finished'] ?? false);
    

                    $manager->persist($transactionBatch);
                    if($transactionBatch && $transactionBatch->getName() && $portfolio && $portfolio->getName() && $user && $user->getUserName())
                    {
//                        echo "Batch {$transactionBatch->getName()} created for portfolio {$portfolio->getName()} of user {$user->getUserName()}\n";                 
                    }
                    $batches[$batchData['name']] = $transactionBatch;
                }
                $this->userBatches[$userBatchesData['user']][$portfolioBatches['portfolio']] = $batches;
            }
        }
    }

    public function loadTransaction(ObjectManager $manager)
    {

        foreach(self::TRANSACTION_DATA as $transactionsData)
        {
            $userName = $transactionsData['user'];
            $portfolio = $transactionsData['portfolio'];
            $currencyRepository = $manager->getRepository(Currency::class);
            $exchangeRepository = $manager->getRepository(Exchange::class);

            $userBatches = $this->userBatches[$userName][$portfolio];
            if(!$userBatches || count($userBatches) == 0)
            {
                echo "No batches found for user {$userName} and portfolio {$portfolio}\n";
                continue;
            }

            foreach($transactionsData['transactionBatches'] as $transactionBatchData)
            {
                $transactionBatch = $userBatches[$transactionBatchData['batchName'] ];
                if (!$transactionBatch) {
                    echo "No batch found with name {$transactionBatchData['batchName']}\n";
                    continue;
                }

                
                foreach($transactionBatchData['transactions'] as $transactionData)
                {
                    //var_dump($transactionData);
                    $transaction = new Transaction();
                    $transaction->setTransactionBatch($transactionBatch);
                    $transaction->setExchange($exchangeRepository->findOneBy([
                        'name' => $transactionData['exchange']
                    ]));
                    $transaction->setDate(\DateTime::createFromFormat('Y-m-d', $transactionData['date']));

                    $transaction->setBoughtCurrency(
                        $currencyRepository->findOneBy(['symbol' => $transactionData['boughtCurrency']])
                    );
                    $transaction->setBuyValue($transactionData['buyValue']);
                    $transaction->setSoldCurrency(
                        $currencyRepository->findOneBy(['symbol' => $transactionData['sellCurrency']])
                    );
                    $transaction->setSellValue($transactionData['sellValue']);
                    $transaction->setFeeCurrency(
                        $currencyRepository->findOneBy(['symbol' => $transactionData['feeCurrency']])
                    );
                    $transaction->setFee($transactionData['fee']);
                    $transaction->setMarketPrice($transactionData['marketPrice'] ?? $transaction->getSellValue()/$transaction->getBuyValue());
                    $transaction->calculateEffectivePrice();

//                    var_dump($transaction);
                    $manager->persist($transaction);

                }
           }
        }
    }
}