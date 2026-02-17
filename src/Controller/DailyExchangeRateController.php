<?php

namespace App\Controller;

use App\Entity\DailyExchangeRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exchangeRates', name: 'app_daily_exchange_rate')]
final class DailyExchangeRateController extends AbstractController
{
    
    private $repository; 

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
        $this->repository = $this->entityManager->getRepository(DailyExchangeRate::class);
    }

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
    public function DeleteExchange(int $id): JsonResponse
    {
        $this->repository->removebyId($id);
        return $this->json([
            'message' => 'Exchange rate deleted'
        ]);
    }
}
