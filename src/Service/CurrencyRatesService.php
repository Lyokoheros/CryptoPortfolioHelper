<?php
namespace App\Service;

use App\Repository\DailyExchangeRateRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTime;
use JsonException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurrencyRatesService
{
    private int $maxRetries = 7;

    public function __construct(
        private DailyExchangeRateRepository $repository,
        private HttpClientInterface $httpClient
    ){}

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
                $this->getHistoricalRate($date->modify('-1 day'), $soldCurrency, $boughtCurrency, $retries + 1);
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