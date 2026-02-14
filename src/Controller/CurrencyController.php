<?php

namespace App\Controller;

use App\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/currency', name: 'app_currency')]
final class CurrencyController extends AbstractController
{
    
    private $repository; 

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
        $this->repository = $this->entityManager->getRepository(Currency::class);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $currencies = $this->repository->findAll();
        return $this->json($currencies, context: ['groups' => 'currencyList']);
    }

    #[Route('/{id<\d+>}', name: 'view', methods: ['GET'])]
    public function getCurrencyById(Currency $currency): JsonResponse
    {
        return $this->json($currency,  context: ['groups' => 'currencyDetails']);
    }

    #[Route('/{symbol<[A-Za-z]+>}', name: 'view_by_symbol', methods: ['GET'])]
    public function getCurrencyBySymbol(string $symbol): JsonResponse
    {
        $currency = $this->repository->findOneBy(['symbol' => $symbol]);
        if ($currency === null) {
            return $this->json(
                ['error' => 'Currency with given symbol not found'], 
                Response::HTTP_NOT_FOUND);
        }
        return $this->json($currency,  context: ['groups' => 'currencyDetails']);
    }


    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editCurrency(Request $request): JsonResponse
    {
        $currencyData = json_decode($request->getContent(), true);

        $id = $currencyData['id'] ?? '-1';
        $symbol = $currencyData['symbol'] ?? null;
        if ($id === '-1' ) 
        {
            if($symbol === null)
            {
                return $this->json(
                    ['error' => 'Currency ID and Symbol missing'], 
                    Response::HTTP_BAD_REQUEST);
            }
            else
            {
                $currency = $this->repository->findOneBy(['symbol' => $symbol]);
                if($currency === null)
                {
                    return $this->json(
                        ['error' => 'Currency with given symbol not found'], 
                        Response::HTTP_BAD_REQUEST);
                }
                $id = $currency->getId();
            }
        }

        $this->repository->editCurrency($id, $currencyData);
        
        return $this->json([
            'message' => 'Currency data changed'
        ]);
    }

    #[Route('/new', name: 'add', methods: ['POST'])]
    public function addCurrency(Request $request): JsonResponse
    {
        $currencyData = json_decode($request->getContent(), true);
        
        $this->repository->addCurrency($currencyData);
        $this->entityManager->flush();
       
        return $this->json([
            'message' => 'Currency added'
        ]);
    }    

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function DeleteCurrency(int $id): JsonResponse
    {
        $this->repository->removebyId($id);
        return $this->json([
            'message' => 'Currency deleted'
        ]);
    }

    #[Route('/delete/{symbol<[A-Za-z]+>}', name: 'delete_by_symbol', methods: ['DELETE'])]
    public function DeleteCurrencyBySymbol(string $symbol): JsonResponse
    {
        $currency = $this->repository->findOneBy(['symbol' => $symbol]);
        if ($currency === null) {
            return $this->json(
                ['error' => 'Currency with given symbol not found'], 
                Response::HTTP_NOT_FOUND);
        }
        $this->repository->removebyId($currency->getId());
        return $this->json([
            'message' => 'Currency deleted'
        ]);
    } 
}
