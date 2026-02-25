<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Service\ReportingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/report', name: 'app_reporting')]
final class ReportController extends AbstractController
{
    private $repository; 

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private ReportingService $service
    ) {
        $this->repository = $this->entityManager->getRepository(Portfolio::class);
    }

    #[Route('/', name: 'index')]
    public function index(): JsonResponse
    {
        return $this->json([
            "usage:" => "{userId}/{endpoint parameter}"
        ]);
    }

    #[Route('/{id<\d+>}', name: 'get_user_summary', methods: ['GET'])]
    public function getPortfolioById(Portfolio $portfolio): JsonResponse
    {
        return $this->json($this->service->getPortfolioSummary($portfolio));
    }

   

}
