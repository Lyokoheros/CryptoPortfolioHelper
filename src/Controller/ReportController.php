<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Repository\CurrencyRepository;
use App\Service\AssetService;
use App\Service\CryptoApi\CoinGeckoApi;
use App\Service\ReportingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/report', name: 'app_reporting')]
final class ReportController extends AbstractController
{
    private $repository; 

    public function __construct(
        //private SerializerInterface $serializer,
        private ReportingService $service,
        private CurrencyRepository $currencyRepository,
        //for testing
        private CoinGeckoApi $coingeckoApi,
        private AssetService $assetService,
        private LoggerInterface $logger
    ) {}

    private function logMemory(string $label): void
    {
        $usage = memory_get_usage(true) / 1024 / 1024;
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        $this->logger->info("[$label] Current: {$usage}MB | Peak: {$peak}MB");
    }

    #[Route('/', name: 'index')]
    public function index(): JsonResponse
    {
        return $this->json([
            "usage:" => "{userId}/{endpoint parameter} or {portfolioID}/{endpoint parameter}"
        ]);
    }

    #[Route('/{id<\d+>}', name: 'get_portfolio_summary', methods: ['GET'])]
    public function getPortfolioSummaryById(Portfolio $portfolio, Request $request): JsonResponse
    {
        $currency = $this->currencyRepository->findOneBy(['symbol' => $request->get('inCurrency')]);
        return $this->json($this->service->getPortfolioSummary(
            $portfolio, 
            $currency
        ));
    }

    #[Route('/value/{id<\d+>}', name: 'get_portfolio_value_summary', methods: ['GET'])]
    public function getPortfolioValueReportById(Portfolio $portfolio, Request $request): JsonResponse
    {
        $this->logMemory("endpoint start");
        $currency = $this->currencyRepository->findOneBy(['symbol' => $request->get('inCurrency')]);
        $this->logMemory("currency found");
        $data = $this->service->getPortfolioValueSummary(
            $portfolio,
            $currency
        );
        $this->logMemory("endpoint end");
        return $this->json($data);
    }

    #[Route('/cost/{id<\d+>}', name: 'get_portfolio_cost_summary', methods: ['GET'])]
    public function getPortfolioCostReportById(Portfolio $portfolio, Request $request): JsonResponse
    {
        $this->logMemory("endpoint start");
        $currency = $this->currencyRepository->findOneBy(['symbol' => $request->get('inCurrency')]);
        $this->logMemory("currency found: " . $currency);
        $data = $this->service->getPortfolioCostSummary(
            $portfolio, 
            $currency
        );
        $this->logMemory("endpoint end");
        return $this->json($data);
    }

    #[Route('/coins/{id<\d+>}', name: 'get_portfolio_coins_summary', methods: ['GET'])]
    public function getPortfolioCoinsReportById(Portfolio $portfolio, Request $request): JsonResponse
    {
        $currency = $this->currencyRepository->findOneBy(['symbol' => $request->get('inCurrency')]);
        return $this->json($this->service->getPortfolioCoinsSummary(
            $portfolio, 
            $currency
        ));
    }


    /*#[Route('/test/{id<\d+>}', name: 'get_user_summary', methods: ['GET'])]
    public function getTestByPortfolioId(Portfolio $portfolio): JsonResponse
    {
        return $this->json($this->coingeckoApi->getCryptoPrices(
            $this->assetService->getBoughtAssets([$portfolio])));
    }*/



   

}
