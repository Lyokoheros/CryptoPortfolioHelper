<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Exchange;

class ExchangeFixtures extends Fixture
{
    const EXCHANGES_DATA = [
        [
            'name' => 'Binnance',
            'mainUrl' => 'https://www.binance.com',
            'parserClass' => 'App\\Parser\\BinnanceParser'
        ],
        [
            'name' => 'Coinbase',
            'mainUrl' => 'https://www.coinbase.com',
            'parserClass' => ''
        ],
        [
            'name' => 'KuCoin',
            'mainUrl' => 'https://www.kucoin.com/',
            'parserClass' => ''
        ],
        [
            'name' => 'SwissBorg',
            'mainUrl' => null,
            'parserClass' => ''
        ]


    ];

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $this->loadExchanges($manager);

        $manager->flush();
    }

    public function loadExchanges(ObjectManager $objectManager): void
    {
        foreach(self::EXCHANGES_DATA as $exchangeData)
        {
            $exchange = new Exchange();
            $exchange->setName($exchangeData['name']);
            $exchange->setMainUrl($exchangeData['mainUrl']);
            $exchange->setParserClass($exchangeData['parserClass']);
            $objectManager->persist($exchange);
        }
        
    }
}
