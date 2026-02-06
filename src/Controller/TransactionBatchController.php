<?php

namespace App\Controller;

use App\Entity\TransactionBatch;
use App\Entity\Portfolio;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/transaction/batch', name: 'app_transaction_batch')]
final class TransactionBatchController extends AbstractController
{
    private $repository; 
    private $portfolioRepository;

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
        $this->repository = $this->entityManager->getRepository(TransactionBatch::class);
        $this->portfolioRepository = $this->entityManager->getRepository(Portfolio::class);
    }

    #[Route('/{portfolioId}', name: 'list_by_portfolio')]
    public function index(string $portfolioId): JsonResponse
    {
        $portfolio = $this->portfolioRepository->find($portfolioId);
        $batches = $this->repository->findBy(['portfolio' => $portfolio]);
        return $this->json($batches);
    }   

    #[Route('/view/{id<\d+>}', name: 'view', methods: ['GET'])]
    public function getTransactionBatchById(TransactionBatch $transactionBatch): JsonResponse
    {
        return $this->json($transactionBatch);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'])]
    public function editTransactionBatch(Request $request): JsonResponse
    {
        $transactionBatchData = json_decode($request->getContent(), true);

        $id = $transactionBatchData['id'] ?? '-1';
        if ($id === '-1') 
        {
            return $this->json(
                ['error' => 'Transaction Batch ID missing'], 
                Response::HTTP_BAD_REQUEST);
        }

        $this->repository->editTransactionBatch($id, $transactionBatchData);
        
        return $this->json([
            'message' => 'Transaction Batch data changed'
        ]);
    }

    #[Route('/new', name: 'add', methods: ['POST'])]
    public function addTransactionBatch(Request $request): JsonResponse
    {
        $userData = json_decode($request->getContent(), true);

        $this->repository->addTransactionBatch($userData);
       
        return $this->json([
            'message' => 'Transaction Batch added'
        ]);
    }    

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function DeleteTransactionBatch(int $id): JsonResponse
    {
        $this->repository->removebyId($id);
        return $this->json([
            'message' => 'Transaction Batch deleted'
        ]);
    }
}