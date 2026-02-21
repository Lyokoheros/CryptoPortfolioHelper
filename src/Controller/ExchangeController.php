<?php

namespace App\Controller;


use App\Entity\Exchange;
use App\Repository\UserRepository;
use App\Service\ExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exchange', name: 'app_exchange')]
final class ExchangeController extends AbstractController
{
    private $repository; 

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepo,
        private ExchangeService $exchangeService
    ) {
        $this->repository = $this->entityManager->getRepository(Exchange::class);
    }

    #[Route('', name: 'list')]
    public function index(): JsonResponse
    {
        $exchanges = $this->repository->findAll();

        return $this->json($exchanges, context: ['groups' => 'exchangeDetails']);
    }

    #[Route('/{id<\d+>}', name: 'view', methods: ['GET'])]
    public function getExchangeById(Exchange $exchange): JsonResponse
    {
        return $this->json($exchange,  context: ['groups' => 'exchangeDetails']);
    }

    #[Route('/{name<[A-Za-z]+>}', name: 'view_by_name', methods: ['GET'])]
    public function getExchangeByName(string $name): JsonResponse
    {
        $exchange = $this->repository->findOneBy(['name' => $name]);
        if ($exchange === null) {
            return $this->json(
                ['error' => 'Exchange with given name not found'], 
                Response::HTTP_NOT_FOUND);
        }
        return $this->json($exchange,  context: ['groups' => 'exchangeDetails']);
    }


    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editExchange(Request $request): JsonResponse
    {
        $exchangeData = json_decode($request->getContent(), true);

        $id = $exchangeData['id'] ?? '-1';
        $name = $exchangeData['name'] ?? null;
        if ($id === '-1' ) 
        {
            if($name === null)
            {
                return $this->json(
                    ['error' => 'Exchange ID and Name missing'], 
                    Response::HTTP_BAD_REQUEST);
            }
            else
            {
                $exchange = $this->repository->findOneBy(['name' => $name]);
                if($exchange === null)
                {
                    return $this->json(
                        ['error' => 'Exchange with given name not found'], 
                        Response::HTTP_BAD_REQUEST);
                }
                $id = $exchange->getId();
            }
        }

        $this->repository->editExchange($id, $exchangeData);
        
        return $this->json([
            'message' => 'Exchange data changed'
        ]);
    }

    #[Route('/new', name: 'add', methods: ['POST'])]
    public function addExchange(Request $request): JsonResponse
    {
        $exchangeData = json_decode($request->getContent(), true);
        
        $this->repository->addExchange($exchangeData);
        $this->entityManager->flush();
       
        return $this->json([
            'message' => 'Exchange added'
        ]);
    }    

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function DeleteExchange(int $id): JsonResponse
    {
        $this->repository->removebyId($id);
        return $this->json([
            'message' => 'Exchange deleted'
        ]);
    }

    #[Route('/importcsvData', name: 'csv_data_import', methods: ['POST'])]
    public function importDataFromCSV(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        $csvContent = $request->files->get('file')->getContent();
        $user = $this->userRepo->find($requestData['userID']);
        $exchangeName = $requestData['exchange'];

        $this->exchangeService->addCSVDataFromExchage(
            $exchangeName, 
            $user, 
            $csvContent
        );

        return $this->json([
                'message' => 'Data added succesfully'
            ]);
    }
}