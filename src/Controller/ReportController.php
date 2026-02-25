<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Repository\CurrencyRepository;
use App\Service\AssetService;
use App\Service\CryptoApi\CoinGeckoApi;
use App\Service\ReportingService;
use Doctrine\ORM\EntityManagerInterface;
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
        private AssetService $assetService
    ) {}

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
        $currency = $this->currencyRepository->findOneBy(['symbol' => $request->get('inCurrency')]);
        return $this->json($this->service->getPortfolioValueSummary(
            $portfolio,
            $currency
        ));
    }

    #[Route('/cost/{id<\d+>}', name: 'get_portfolio_cost_summary', methods: ['GET'])]
    public function getPortfolioCostReportById(Portfolio $portfolio, Request $request): JsonResponse
    {
        $currency = $this->currencyRepository->findOneBy(['symbol' => $request->get('inCurrency')]);
        return $this->json($this->service->getPortfolioCostSummary(
            $portfolio, 
            $currency
        ));
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
