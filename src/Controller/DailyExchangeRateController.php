<?php

namespace App\Controller;

use App\Entity\DailyExchangeRate;
use App\Repository\DailyExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exchangeRates', name: 'app_daily_exchange_rate')]
final class DailyExchangeRateController extends AbstractController
{
    
    
    public function __construct(
        private DailyExchangeRateRepository $repository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'list')]
    public function index(): JsonResponse
    {
        $dailyExchangeRates = $this->repository->findAll();

        return $this->json($dailyExchangeRates, context: ['groups' => 'exchangeRate']);
    }

    #[Route('/{id<\d+>}', name: 'view', methods: ['GET'])]
    public function getExchangeRateById(DailyExchangeRate $dailyExchangeRate): JsonResponse
    {
        return $this->json($dailyExchangeRate,  context: ['groups' => 'exchangeRate']);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editExchangeRate(Request $request): JsonResponse
    {
        $exchangeRateData = json_decode($request->getContent(), true);

        $id = $exchangeRateData['id'] ?? '-1';
        if ($id === '-1' ) 
        {           
            return $this->json(
                ['error' => 'ExchangeRate ID missing'], 
                Response::HTTP_BAD_REQUEST);           
        }

        $this->repository->editExchangeRate($id, $exchangeRateData);
        
        return $this->json([
            'message' => 'Exchange data changed'
        ]);
    }

    #[Route('/new', name: 'add', methods: ['POST'])]
    public function addExchangeRate(Request $request): JsonResponse
    {
        $exchangeRateData = json_decode($request->getContent(), true);
        
        $this->repository->addExchangeRate($exchangeRateData);
        $this->entityManager->flush();
       
        return $this->json([
            'message' => 'Exchange rate added'
        ]);
    }    

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function DeleteExchangeRate(int $id): JsonResponse
    {
        $this->repository->removebyId($id);
        return $this->json([
            'message' => 'Exchange rate deleted'
        ]);
    }

    #[Route('/reset', name: 'delete_all', methods: ['DELETE'])]
    public function deleteAllExchangeRates(): JsonResponse
    {
        $this->repository->removeAll();
        return $this->json([
            'message' => 'All exchange rates deleted'
        ]);
    }
}
