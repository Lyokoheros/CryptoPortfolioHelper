<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use App\Service\CryptoApi\CoinGeckoApi;
use App\Service\CryptoApi\CryptoApiProviderInterface;
use DateTime;
use Psr\Log\LoggerInterface;

class CurrencyService
{   
    private array $priceCache = [];
    private string $updateFrequency = '600 minutes';
    private DateTime $lastCacheRefresh;

    public function __construct(
        private CurrencyRepository $currencyRepo,
        private CryptoApiProviderInterface $cryptoApi,
        private CoinGeckoApi $coinGecko,
        private LoggerInterface $logger
    ) {
        $this->lastCacheRefresh = new DateTime();
    }

    private function logMemory(string $label): void
    {
        $usage = memory_get_usage(true) / 1024 / 1024;
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        $this->logger->info("[$label] Current: {$usage}MB | Peak: {$peak}MB");
    }

    public function getCache():void
    {
        var_dump($this->priceCache);
    }
    public function checkPriceCache(): void
    {
        $now = new DateTime();
        $dateLimit= (clone $now)->modify('-'.$this->updateFrequency);

        if($this->lastCacheRefresh < $dateLimit)
        {
            $this->priceCache = [];
            $this->lastCacheRefresh = $now;
        }        
    }

    public function updatePrice(Currency $currency): float
    {
        $now = new DateTime();
        
        $fiat = $this->currencyRepo->isInNiche($currency, 'fiat');

        if($fiat)
        {//fiat prices update will be implemented later
            return $currency->getCurrentPrice();
        }

        if ($this->shouldUpdatePrice($currency) && !$fiat) 
        {
            $price = $this->cryptoApi->getCryptoPrice($currency);
            $currency->setLastPriceUpdate($now);
        }
        else 
        {
            $price = $currency->getCurrentPrice();
        }
        $currency->setCurrentPrice($price);
        

        $this->currencyRepo->saveEntity($currency);
        return $price;
    }

    private function shouldUpdatePrice(Currency $currency): bool
    {
        $lastUpdate = $currency->getLastPriceUpdate();
        $dateLimit= (new DateTime())->modify('-'.$this->updateFrequency);
        
        return $lastUpdate === null || $lastUpdate < $dateLimit;
    }

    public function updatePriceInBullk(array $currencies): void
    {
        foreach($currencies as $currency)
        {
            if($this->shouldUpdatePrice($currency))
            {
                $prices = $this->coinGecko->getCryptoPrices($currencies);
                $assets = $currencies;
                
                foreach($assets as $currency)
                {
                    $currency->setCurrentPrice($prices[$currency->getSymbol()]);
                    $this->currencyRepo->saveEntity($currency);
                }  

                break;                
            }
        }        
    }

    public function translateStableCoinToFiat(Currency $stableCoin): ?Currency
    {
        $fiats = $this->currencyRepo->findFiatCurrencies();

        foreach($fiats as $fiatCurrency)
        {
            $symbol = $fiatCurrency->getSymbol();
            if(str_contains($stableCoin->getSymbol(), $symbol))
            {
                return $fiatCurrency;
            }
        }
        return null;            
    }

    public function getStablesForFiat(Currency $fiatCurrency): array
    {
        $stableCoins = [];
        $stables = $this->currencyRepo->findStableCoins();
        $symbol = $fiatCurrency->getSymbol();
        foreach($stables as $stableCoin)
        {
            if(str_contains($stableCoin->getSymbol(), $symbol))
            {
                $stableCoins[] = $stableCoin;
            }
        }
        return $stableCoins;            
    }

    public function getPrice(Currency $asset, Currency $baseCurrency): ?float
    {
        $this->checkPriceCache();
        $assetSymbol = $asset->getSymbol();
        $baseSymbol = $baseCurrency->getSymbol();
        if(isset($this->priceCache[$assetSymbol][$baseSymbol]))
        {
            return $this->priceCache[$assetSymbol][$baseSymbol];
        }
        $this->updatePrice($asset);
            
        $currency = $this->currencyRepo->findOneBy([
            'symbol' => $assetSymbol,
            'pricesCurrency' => $baseCurrency
        ]);

        if($currency)
        {
            $this->updatePrice($currency);
            $price = $currency->getCurrentPrice();
            $this->priceCache[$assetSymbol][$baseSymbol] = $price;
            $this->priceCache[$baseSymbol][$assetSymbol] = 1.0 / $price;
            return $price;
        }
        else
        {
            if(isset($this->priceCache[$baseSymbol][$assetSymbol]))
            {
                $this->priceCache[$assetSymbol][$baseSymbol] = 1.0/$this->priceCache[$baseSymbol][$assetSymbol];
                return $this->priceCache[$assetSymbol][$baseSymbol];
            }
            $currency = $this->currencyRepo->findOneBy([
                'symbol' => $baseSymbol, 
                'pricesCurrency' => $asset
            ]);
            
            if($currency)
            {
                $price = $this->updatePrice($currency);
                $this->priceCache[$assetSymbol][$baseSymbol] = 1.0/$price;                
                $this->priceCache[$baseSymbol][$assetSymbol] = $price;
                                
                return (1.0 / $price);
            }
            else
            {
                $priceSymbol = $asset->getPricesCurrency()->getSymbol();
                if(
                    isset($this->priceCache[$assetSymbol][$priceSymbol]) &&
                    isset($this->priceCache[$priceSymbol][$baseSymbol])
                ) {
                    $this->priceCache[$assetSymbol][$baseSymbol] = 
                        $this->priceCache[$assetSymbol][$priceSymbol]
                        * $this->priceCache[$priceSymbol][$baseSymbol];

                    return $this->priceCache[$assetSymbol][$baseSymbol];
                }
                $currency = $this->currencyRepo->findOneBy([
                    'symbol' => $priceSymbol,
                    'pricesCurrency' => $baseCurrency
                ]);
                
                if($currency)
                {
                    $price = $this->updatePrice($currency);
                    
                    $this->priceCache[$priceSymbol][$baseSymbol] =  $price;
                    $this->priceCache[$baseSymbol][$priceSymbol] =  1.0 / $price;
                    
                    $price *=$asset->getCurrentPrice();
                    $this->priceCache[$assetSymbol][$baseSymbol] = $price;
                    $this->priceCache[$baseSymbol][$assetSymbol] = 1.0 / $price;

                    return $price;
                }
                else
                {
                    if(
                        isset($this->priceCache[$assetSymbol][$priceSymbol]) &&
                        isset($this->priceCache[$baseSymbol][$priceSymbol])
                    ) {
                        $this->priceCache[$assetSymbol][$baseSymbol] = 
                        $this->priceCache[$assetSymbol][$priceSymbol]
                        / $this->priceCache[$baseSymbol][$priceSymbol];

                    return $this->priceCache[$assetSymbol][$baseSymbol];

                    }
                    $currency = $this->currencyRepo->findOneBy([
                        'symbol' => $baseSymbol,
                        'pricesCurrency' => $asset->getPricesCurrency()
                    ]);
                    if($currency)
                    {
                        $price = $this->updatePrice($currency);
                        $this->priceCache[$baseSymbol][$priceSymbol] =  $price;
                        $this->priceCache[$priceSymbol][$baseSymbol] =  1.0 / $price;
                    
                        $price = $asset->getCurrentPrice() / $price;
                        $this->priceCache[$baseSymbol][$assetSymbol] = $price;
                        $this->priceCache[$assetSymbol][$baseSymbol] = 1.0 / $price;

                        return $price;
                    }
                }
            }
        }
        return null;
    }



    public function updateCoinGeckoIds(): array //to be removed
    {
        $allCurrencies = $this->currencyRepo->findAll();
        $coinGeckoData = null;
        $updates = 0;
        $output = [];

        foreach($allCurrencies as $currency)
        {
            if($currency->getCoinGeckoID() === null || true)            
            {
                if($coinGeckoData === null)
                {
                    $coinGeckoData = $this->coinGecko->getApiCoinsList();
                }
            }
            $symbol = strtolower($currency->getSymbol());
            $coinGeckoData ??= [];
            foreach($coinGeckoData as $coinData)
            {
                if($coinData['symbol'] == $symbol)
                {
                    $currency->setCoinGeckoID($coinData['id']);
                    $this->currencyRepo->saveEntity($currency);
                    $updates++;
                    $output []= $symbol . " => " .$coinData['id'];
                }
            }
        }
        return [
            'updates' => $updates,
            'output' => $output
        ];
    }

    

}