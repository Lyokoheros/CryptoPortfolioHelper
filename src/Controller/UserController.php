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
    
    #[Route('/{username}', name: 'get_by_username', methods: ['GET'])]
    public function getUserByName(string $username): JsonResponse
    {        
        return $this->json([
            $this->repository->findOneBy(['username' => $username])
        ]);
    }

    #[Route('/{id<\d+>}', name: 'get_by_id', methods: ['GET'])]
    public function getUserById(User $user): JsonResponse
    {
        return $this->json($user);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function EditUser(Request $request): JsonResponse
    {
        $userData = json_decode($request->getContent(), true);

        $this->repository->editUser($userData);
        
        return $this->json([
            'message' => 'User data changed'
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function DeleteUser(): JsonResponse
    {
        return $this->json([
            'message' => 'User deleted [to do]'
        ]);
    }    
}
