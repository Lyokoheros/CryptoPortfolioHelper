<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user_', name: 'app_user')]
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
            'message' => 'User List[to do]'
        ]);
    }

    //user registration and login [to do in later versions]
    
    #[Route('/{username}', name: 'get_by_username', methods: ['GET'])]
    public function getUserByName(): JsonResponse
    {
        return $this->json([
            'message' => 'User data [to do]'
        ]);
    }

    #[Route('/{id<\d+>}', name: 'get_by_id', methods: ['GET'])]
    public function getUserById(): JsonResponse
    {
        return $this->json([
            'message' => 'User data [to do]'
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function EditUser(): JsonResponse
    {
        return $this->json([
            'message' => 'User data changed [to do]'
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
