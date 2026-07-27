<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Currency;
use App\Entity\DailyExchangeRate;
use App\Entity\Exchange;
use App\Entity\Portfolio;
use App\Entity\TransactionBatch;
use App\Repository\UserRepository;
use App\Service\ReportingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/user', name: 'app_user')]
class UserController extends AbstractController
{

    public function __construct(
        //private SerializerInterface $serializer,
        private UserRepository $repository,
        private ReportingService $reportingService
    ) {}

    #[Route('/', name: 'list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            $this->repository->getAllUsers()
        ]);
    }

    #[Route('/test', name: 'test', methods: ['GET'])]
    public function testCurrency(UserRepository $userRepository, SerializerInterface $serializer): Response
    {
        $users = $userRepository->findAll();
        $currencies = $userRepository->getEntityManager()
            ->getRepository(Currency::class)
            ->findAll();
        $portfolios = $userRepository->getEntityManager()
            ->getRepository(Portfolio::class)
            ->findAll();    
        $transactionBatches = $userRepository->getEntityManager()
            ->getRepository(TransactionBatch::class)
            ->findAll();
        $transactions = $userRepository->getEntityManager()
            ->getRepository(Transaction::class)
            ->findAll();
        $DailyExchangeRates = $userRepository->getEntityManager()
            ->getRepository(DailyExchangeRate::class)
            ->findAll();
        $exchanges = $userRepository->getEntityManager()
            ->getRepository(Exchange::class)
            ->findAll();

        $database = [
        'users' => json_decode($serializer->serialize($users, 'json', ['groups' => 'userProfile']), true),
        'portfolios' => json_decode($serializer->serialize($portfolios, 'json', ['groups' => 'portfolioView']), true),
        'transactionBatches' => json_decode($serializer->serialize($transactionBatches, 'json', ['groups' => 'transactionBatchList']), true),
        'transactions' => json_decode($serializer->serialize($transactions, 'json', ['groups' => 'transactionDetails']), true),
        'DailyExchangeRates' => json_decode($serializer->serialize($DailyExchangeRates, 'json'), true),
        'exchanges' => json_decode($serializer->serialize($exchanges, 'json', ['groups' => 'exchangeDetails']), true),
        'currencies' => json_decode($serializer->serialize($currencies, 'json', ['groups' => 'currencyDetails']), true)
    ];
        
        return $this->json($database);
    }

    //user registration and login [to do in later versions]
    
    #[Route('/{id<\d+>}', name: 'get_by_id', methods: ['GET'])]
    public function getUserById(User $user): JsonResponse
    {
        return $this->json($user, context: ['groups' => 'userProfile']);
    }
    
    #[Route('/{username}', name: 'get_by_username', methods: ['GET'])]
    public function getUserByName(string $username): JsonResponse
    { 
        echo $username;
        $user = $this->repository->findOneBy(['userName' => $username]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($user, context: ['groups' => 'userProfile']);
    }
    

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editUser(Request $request): JsonResponse
    {
        $userData = json_decode($request->getContent(), true);

        $id = $userData['id'] ?? '-1';
        if ($id === '-1') 
        {
            return $this->json(
                ['error' => 'User ID missing'], 
                Response::HTTP_BAD_REQUEST);
        }

        $this->repository->editUser($id, $userData);
        
        return $this->json([
            'message' => 'User data changed'
        ]);
    }

    #[Route('/new', name: 'register', methods: ['POST'])]
    public function registerUser(Request $request): JsonResponse
    {
        $userData = json_decode($request->getContent(), true);

        $this->repository->registerUser($userData);
       
        return $this->json([
            'message' => 'User registered'
        ]);
    }    

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $this->repository->removeUser($id);
        return $this->json([
            'message' => 'User deleted'
        ]);
    }    


    #[Route('/pit/{year<\d+>}', name: 'pit_data', methods: ['GET'])]
    public function getPitData(int $year): JsonResponse
    {

        $users = $this->repository->getAllUsers();
        $usersData = [];
        foreach ($users as $user) {
           // echo $user->getName();
            $usersData[$user->getName()] = $this->reportingService->getPitReportData($user, $year);
        }
        return $this->json($usersData);
    }
}
 