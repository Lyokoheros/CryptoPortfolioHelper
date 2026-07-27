<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\TransactionBatch;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/transaction', name: 'app_transaction')]
final class TransactionController extends AbstractController
{

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private TransactionRepository $repository,
        private UserRepository $userRepository
    ) {
    }


    #[Route('/{id<\d+>}', name: 'view', methods: ['GET'])]
    public function getTransactionBatchById(Transaction $transaction): JsonResponse
    {
        return $this->json($transaction,  context: ['groups' => 'transactionDetails']);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editTransaction(Request $request): JsonResponse
    {
        $transactionData = json_decode($request->getContent(), true);

        $id = $transactionData['id'] ?? '-1';
        if ($id === '-1') 
        {
            return $this->json(
                ['error' => 'Transaction ID missing'], 
                Response::HTTP_BAD_REQUEST);
        }

        $this->repository->editTransaction($id, $transactionData);
        
        return $this->json([
            'message' => 'Transaction Batch data changed'
        ]);
    }

    #[Route('/new', name: 'add', methods: ['POST'])]
    public function addTransaction(Request $request): JsonResponse
    {
        $transactionData = json_decode($request->getContent(), true);
        $batchId = $transactionData['batchId'] ?? '-1';
        if ($batchId === '-1')
        {
            return $this->json(
                ['error' => 'Transaction Batch ID missing'], 
                Response::HTTP_BAD_REQUEST);
        }
        $this->repository->addTransaction($transactionData);
        $this->entityManager->flush();
       
        return $this->json([
            'message' => 'Transaction added'
        ]);
    }    

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function DeleteTransaction(int $id): JsonResponse
    {
        $this->repository->removebyId($id);
        return $this->json([
            'message' => 'Transaction deleted'
        ]);
    }

    #[Route('/user/{userId}', name: 'get_user_transactions', methods: ['GET'])]
    public function getAllUserTransactions(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        $optionalCriteria = [
            'startDate' => (new \DateTime())->setDate(2025, 1, 1)->setTime(0, 0, 0),
            'endDate' => (new \DateTime())->setDate(2025, 12, 31)->setTime(23, 59, 59)];
        $transactions = $this->repository->findAllByUser($user, $optionalCriteria);
        return $this->json($transactions, context: ['groups' => 'transactionDetails']);
    }
}
