<?php

namespace App\Repository;

use App\Entity\Portfolio;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<Portfolio>
 */
class PortfolioRepository extends EnhancedEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    public function addPortfolio($portfolioData): Portfolio
    {
        
        $portfolio = new Portfolio();
        //to do: check for required fields (user_id, portfolio_name)                
        $portfolioData['batchSize'] ??= 1;
        $portfolioData['isDefault'] ??= false;
        $portfolioData['totalPortfolioValue'] ??= 0.0;
        //starting date is set in constructor to current date 
        
        
        $this->editEntity($portfolio, $portfolioData);

        return $portfolio;
    }

    public function removePortfolio(Portfolio $portfolio): void
    {
        $this->removeEntity($portfolio);
    }

    public function findOrCreatePortfolio(string $portfolioName, array $portfolioData): Portfolio
    {
        return $this->findOneBy(['name' => $portfolioName]) ?? $this->addPortfolio([
            ...['name' => $portfolioName],
            ...$portfolioData
        ]);
    }
    
}
