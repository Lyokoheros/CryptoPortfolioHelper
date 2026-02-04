<?php

namespace App\Controller;

use App\Entity\Portfolio;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/portfolio', name: 'app_portfolio')]
final class PortfolioController extends AbstractController
{
    private $repository; 

    public function __construct(
        //private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
        $this->repository = $this->entityManager->getRepository(Portfolio::class);
    }

    #[Route('/list/{userId}', name: 'list')]
    public function index(int $userId): JsonResponse
    {
        $portfolios = $this->repository->findBy(['user' => $userId]);
        return $this->json($portfolios, context: ['groups' => 'portfolioList']);
    }

    #[Route('/view/{id<\d+>}', name: 'get_details', methods: ['GET'])]
    public function getPortfolioById(Portfolio $portfolio): JsonResponse
    {
        return $this->json($portfolio, context: ['groups' => 'portfolioView']);
    }

    #[Route('/update/{id<\d+>}', name: 'update_value', methods: ['GET'])]
    public function updatePortfolioValueById(Portfolio $portfolio): Response
    {
        // Logic to update portfolio value goes here
        
        // Redirect to the view endpoint
        return $this->redirectToRoute('get_portfolio_details', ['id' => $portfolio->getId()]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function addPortfolio(Request $request): JsonResponse
    {
        $portfolioData = json_decode($request->getContent(), true);
        $portfolio = $this->repository->addPortfolio($portfolioData);

        return $this->json($portfolio, context: ['groups' => 'portfolioView']);
    }

    #[Route('/delete/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function deletePortfolio(Portfolio $portfolio): JsonResponse
    {
        $this->repository->removePortfolio($portfolio);
        return $this->json(['message' => 'Portfolio deleted successfully']);
    }

}
