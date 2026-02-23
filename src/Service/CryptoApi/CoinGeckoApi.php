<?php

namespace App\Service\CryptoApi;

use App\Entity\Currency;
use App\Service\CryptoApi\CryptoApiProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoinGeckoApi implements CryptoApiProviderInterface
{
    private const BASE_URL = 'https://api.coingecko.com/api/v3';
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'COINGECKO_API_KEY')]
        private readonly string $apiKey
    ) {}
    
    public function getCryptoPrice(Currency $currency, string $priceCurrency = 'USD'): float
    {
        $priceCurrency = strtolower($priceCurrency);
        $response = $this->httpClient->request('GET', self::BASE_URL . '/simple/price', [
            'headers' => [
                'x-cg-pro-api-key' => $this->apiKey,
            ],    
            'query' => [
                'symbols' => strtolower($currency->getSymbol()),
                'vs_currencies' => $priceCurrency,
            ]
        ]);
        
        $data = $response->toArray();
        return $data[strtolower($currency->getSymbol())][$priceCurrency] 
            ?? $data[strtolower($currency->getName())][$priceCurrency] 
            ?? -1;
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