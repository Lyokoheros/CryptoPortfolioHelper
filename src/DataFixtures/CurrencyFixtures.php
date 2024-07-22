<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Currency;

class CurrencyFixtures extends Fixture
{
    const FIAT_CURRENCIES_DATA = [
        [
            'name' => 'Polski Złoty',
            'symbol' => 'PLN'
        ],
        [
            'name' => 'Dolar Amerykański',
            'symbol' => 'USD'
        ],
        [
            'name' => 'Euro',
            'symbol' => 'EUR'
        ],
    ];

    const CRYPTO_CURRENCIES_DATA = [
        [
            'name' => 'Tether USD',
            'symbol' => 'USDT',
            'nativeBlockchain' => null,
            'availableNetworks' => ['Ethereum', 'EOS', 'Tron', 'Solana', 'Optimism'],
            'niches' => ['stablecoins'],
            'allTimeHigh' => 1.32,
            'allTimeLow' => 0.5725,
            'currentPrice' => 1,
            'currentSupply' => 113224555138,
            'maxSupply' => null,            
        ],
        [
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'nativeBlockchain' => 'Bitcoin',
            'availableNetworks' => ['Bitcoin'],
            'niches' => ['layer 1', 'store of value'],
            'allTimeHigh' => 73780.07,
            'allTimeLow' => 0.05,
            'currentPrice' => 64264,
            'currentSupply' => 19727053,
            'maxSupply' => 21000000
        ],
        [
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'nativeBlockchain' => 'Ethereum',
            'availableNetworks' => ['Ethereum'],
            'niches' => ['layer 1', 'smart contracts'],
            'allTimeHigh' => 4878.26,
            'allTimeLow' => 0.433,
            'currentPrice' => 3520.09,
            'currentSupply' => 120224183,
            'maxSupply' => null
        ],
        [
            'name' => 'Internet computer blockchain',
            'symbol' => 'ICP',
            'nativeBlockchain' => 'Internet Computer Protocol',
            'availableNetworks' => [''],
            'niches' => ['web3'],
            'allTimeHigh' => 700.65,
            'allTimeLow' => 2.87,
            'currentPrice' => 10.26,
            'currentSupply' => 521211313,
            'maxSupply' => null
        ],
        [
            'name' => 'Chainlink',
            'symbol' => 'LINK',
            'nativeBlockchain' => 'Ethereum',
            'availableNetworks' => ['Ethereum Network'],
            'niches' => ['oracles'],
            'allTimeHigh' => 52.70,
            'allTimeLow' => 0.1482,
            'currentPrice' => 14.23,
            'currentSupply' => 608099971,
            'maxSupply' => 1000000000
        ],
        [
            'name' => 'Binnace Coin',
            'symbol' => 'BNB',
            'nativeBlockchain' => 'Binnace Smart Chain(BSC)',
            'availableNetworks' => ['Binnace Smart Chain(BSC)', 'Polygon Network', 'Avalanche Network'],
            'niches' => ['layer 1', 'exchange token', 'smart contracts'],
            'allTimeHigh' => 717,48,
            'allTimeLow' => 0.03982,
            'currentPrice' => 588.88,
            'currentSupply' => 153856150,
            'maxSupply' => 200000000
        ]/*,
        [
            'name' => '',
            'symbol' => '',
            'nativeBlockchain' => '',
            'availableNetworks' => [''],
            'niches' => [''],
            'allTimeHigh' => '',
            'allTimeLow' => '',
            'currentPrice' => '',
            'currentSupply' => 1,
            'maxSupply' => null
        ]*/      
    ];
    private $fiatCurrencies = [];
    private $cryptoCurrencies = [];

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $this->loadFiatMoney($manager);
        $this->loadCryptoCurrencies($manager);

        $manager->flush();
    }

    public function loadFiatMoney(ObjectManager $objectManager): void
    {
        foreach(self::FIAT_CURRENCIES_DATA as $currencyData)
        {
            $currency = new Currency();
            $currency->setName($currencyData['name']);
            $currency->setSymbol($currencyData['symbol']);
            $currency->setNiches(['fiat']);
            $objectManager->persist($currency);
            $this->fiatCurrencies[$currencyData['symbol']]=$currency;
        }

        foreach($this->fiatCurrencies as $fiatCurrency)
        {
            $fiatCurrency->setPricesCurrency($this->fiatCurrencies['PLN']);
            $objectManager->persist($currency);
        }
    }

    public function loadCryptoCurrencies(ObjectManager $objectManager): void
    {
        foreach(self::CRYPTO_CURRENCIES_DATA as $currencyData)
        {
            $currency = new Currency();
            $currency->setName($currencyData['name']);
            $currency->setSymbol($currencyData['symbol']);
            $currency->setNativeBlockchain($currencyData['nativeBlockchain']);
            $currency->setAvailableNetworks($currencyData['availableNetworks']);
            $currency->setNiches($currencyData['niches']);
            $currency->setAllTimeHigh($currencyData['allTimeHigh']);
            $currency->setAllTimeLow($currencyData['allTimeLow']);
            $currency->setCurrentPrice($currencyData['currentPrice']);
            $currency->setMaxSupply($currencyData['maxSupply']);
            $currency->setCurrentSupply($currencyData['currentSupply']);
            $currency->setPricesCurrency($this->fiatCurrencies['USD']);

            $objectManager->persist($currency);
            $this->cryptoCurrencies[$currencyData['symbol']]=$currency;
        }
    }
}
