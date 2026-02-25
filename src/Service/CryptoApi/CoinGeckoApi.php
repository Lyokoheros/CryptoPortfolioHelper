<?php

namespace App\Service\CryptoApi;

use App\Entity\Currency;
use App\Service\CryptoApi\CryptoApiProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoinGeckoApi implements CryptoApiProviderInterface
{
    private const BASE_URL = 'https://api.coingecko.com/api/v3';

    private const COIN_GECKO_IDS = [
        'BTC' => 'bitcoin',
        'ETH' => 'ethereum',
//altcoins
        'AAVE' => 'aave',
        'ACE' => 'endurance',
        'ADA' => 'cardano',
        'ALGO' => 'algorand',
        'AR' => 'arweave',
        'ATOM' => 'cosmos',
        'AVAX' => 'avalanche-2',
        'BNB' => 'binancecoin',
        'CVX' => 'convex-finance',
        'DOGE' => 'dogecoin',
        'DOT' => 'polkadot',
        'FIL' => 'filecoin',
        'GALA' => 'gala',
        'GRT' => 'the-graph',
        'ICP' => 'internet-computer',
        'KDA' => 'kadena',
        'KNC' => 'kyber-network-crystal',
        'LINK' => 'chainlink',
        'LUNA' => 'terra-luna',
        'MANA' => 'decentraland',
        'MATIC' => 'polygon-ecosystem-token',
        'NEAR' => 'near',
        'POL' => 'polygon-ecosystem-token',
        'SEI' => 'sei-network',
        'SOL' => 'solana',
        'TRB' => 'tellor',
        'TRX' => 'TRON',
//stablecoins
        'USDT' => 'tether',
        'BUSD' => 'binance-usd',
        'USDC' => 'usd-coin'
    ];
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'COINGECKO_API_KEY')]
        private readonly string $apiKey
    ) {}
    
    public function getCryptoPrice(Currency $currency, string $priceCurrency = 'usd'): float
    {
        $priceCurrency = strtolower($priceCurrency);
        if($currency->getSymbol() == "EUR")
        {
            var_dump($currency->getPricesCurrency()->getSymbol());
        }
        
        $coinGeckoId = self::COIN_GECKO_IDS[$currency->getSymbol()];
        $response = $this->httpClient->request('GET', self::BASE_URL . '/simple/price', [
            'headers' => [
                'x-cg-demo-api-key' => $this->apiKey
            ],
            'query' => [
                'ids' => $coinGeckoId,
                'vs_currencies' => $priceCurrency
            ]
        ]);
        
        $data = $response->toArray();

        if(!isset($data[$coinGeckoId][$priceCurrency]))
        {
            var_dump($data[$coinGeckoId]);
        }

        return $data[$coinGeckoId][$priceCurrency] ?? 0;
    }

    public function getCryptoPrices(array $currencies, string $priceCurrency = 'usd'): array
    {
        $prices = [];
        $ids = [];
        foreach($currencies as $currency)
        {
            $ids[]= self::COIN_GECKO_IDS[$currency->getSymbol()];
        }

        $ids = implode(',', $ids);
        $priceCurrency = strtolower($priceCurrency);

        $response = $this->httpClient->request('GET', self::BASE_URL . '/simple/price', [
            'headers' => [
                'x-cg-demo-api-key' => $this->apiKey
            ],
            'query' => [
                'ids' => $ids,
                'vs_currencies' => $priceCurrency
            ]
        ]);
        
        $data = $response->toArray();
        //return ['data' => $data,'ids' => $ids];

        foreach($currencies as $currency)
        {
            $coinGeckoId = self::COIN_GECKO_IDS[$currency->getSymbol()];
            $prices[$currency->getSymbol()] = $data[$coinGeckoId][$priceCurrency];
        }

        
        return $prices;
    }
    

    public function getApiCoinsList(): array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . '/coins/list', [
            'headers' => ['x-cg-pro-api-key' => $this->apiKey]
        ]);
        
        $coins = $response->toArray();
        
        return $coins;
    }
}