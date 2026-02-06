<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Currency;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user', name: 'app_user')]
class UserController extends AbstractController
{
    private $repository; 

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
        $this->repository = $this->entityManager->getRepository(User::class);
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            $this->repository->getAllUsers()
        ]);
    }

    #[Route('/test', name: 'test', methods: ['GET'])]
    public function testCurrency(UserRepository $userRepository): Response
    {
        $currency = $userRepository->getEntityManager()
            ->getRepository(Currency::class)
            ->findOneBy(['symbol' => 'PLN']);

        if (null === $currency) {
            throw new \RuntimeException('Currency not found');
        }

        return new Response(sprintf('The currency "%s" exists.', $currency->getSymbol()));
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
}
 