<?php
namespace App\Service;

use App\Entity\Currency;
use App\Entity\DailyExchangeRate;
use App\Repository\DailyExchangeRateRepository;
use App\Repository\CurrencyRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTime;
use JsonException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurrencyRatesService
{
    private int $maxRetries = 7;

    public function __construct(
        private DailyExchangeRateRepository $repository,
        private CurrencyRepository $currencyRepo,
        private HttpClientInterface $httpClient
    ){}

    public function checkCurrencyRate(Currency $soldCurrency, Currency $boughtCurrency, DateTime $date): float
    {
        $existing = $this->repository->findOneBy([
            'baseCurrency' => $soldCurrency,
            'exchangedCurrency' => $boughtCurrency,
            'date' => $date
        ]);

        if ($existing) {
            return $existing->getExchangeRate(); // Skip API call entirely
        }
        $exchangeRateData = $this->getHistoricalRate($date, $soldCurrency->getSymbol(), $boughtCurrency->getSymbol());
                       
        $this->repository->addExchangeRateIfNotExists([
            'baseCurrency' => $soldCurrency,
            'exchangedCurrency' => $boughtCurrency,
            'exchangeRate' => $exchangeRateData['rate'],
            'date' => $exchangeRateData['date']
        ]);
        if ($exchangeRateData['date'] === $date->format('Y-m-d')) {
            return $exchangeRateData['rate'];
        }

        $finalDateString = $date->format('Y-m-d');
        $date = new DateTime($exchangeRateData['date']);
        $date->modify('+1 day');  
        $dateString = $date->format('Y-m-d');

        while($dateString !== $finalDateString)
        {
            $this->repository->addExchangeRateIfNotExists([
                'baseCurrency' => $soldCurrency,
                'exchangedCurrency' => $boughtCurrency,
                'exchangeRate' => $exchangeRateData['rate'],
                'date' => $date
            ]);

            $date->modify('+1 day');  
            $dateString = $date->format('Y-m-d');
        }

        return $exchangeRateData['rate'];
    }


    public function getHistoricalRate(
        DateTime $date,
        string $soldCurrency,
        string $boughtCurrency = 'PLN',
        int $retries = 0,
    ): array {
        $dateString = $date->format('Y-m-d');
        
        $soldCurrency = strtoupper($soldCurrency);
        $boughtCurrency = strtoupper($boughtCurrency);

        // Frankfurter API endpoint for a specific pair on a specific date
        $url = sprintf(
            'https://api.frankfurter.dev/v2/rate/%s/%s?date=%s',
            $soldCurrency,
            $boughtCurrency,
            $dateString
        );

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            // The API returns { "date": "2024-01-01", "rate": 4.32 }
            if (isset($data['rate']) and isset($data['date'])) {
                $data['rate'] = floatval($data['rate']);
                return $data;
            }
            else if ($retries < $this->maxRetries) {
                return $this->getHistoricalRate($date->modify('-1 day'), $soldCurrency, $boughtCurrency, $retries + 1);
            }

            throw new NotFoundHttpException("Rate not found for {$soldCurrency} to {$boughtCurrency} on {$dateString}");

        } catch (JsonException $e) {
            // Handle malformed JSON or API errors
            throw new NotFoundHttpException("Invalid response from currency API", $e);
        } catch (\Exception $e) {
            // Handle network errors
            throw new NotFoundHttpException("Failed to fetch exchange rate", $e);
        }
    }
}