<?php

namespace App\DataFixtures;

use App\Entity\Portfolio;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PortfolioFixtures extends Fixture implements DependentFixtureInterface
{
    const USERS_PORTFOLIO_DATA = [
        [
            'Lyokoheros' => [
                [
                    'default' => false,
                    'name' => 'DCA portfolio',
                    'batchSize' => 3,
                    'date' => new \DateTime('')
                ]
            ]
                        
        ]
    ];

    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $this->loadPortfolios($manager);

        $manager->flush();
    }

    public function loadPortfolios(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();


        foreach($users as $user)
        {
            //defualt portfolio
            $portfolio = new Portfolio();
            $portfolio->setUser($user);
            $portfolio->setDefault(true);
            $portfolio->setStartingDate(new \DateTime());

            $manager->persist($portfolio);
            if (isset(self::USERS_PORTFOLIO_DATA[$user->getUserName()]))
            {
                foreach(self::USERS_PORTFOLIO_DATA[$user->getUserName()] as $portfolioData)
                {
                    $portfolio = new Portfolio();
                    $portfolio->setUser($user);
                    $portfolio->setName($portfolioData['name'] ?? null);
                    $portfolio->setBatchSize($portfolioData['batchSize'] ?? null);
                    $portfolio->setStartingDate($portfolioData['date'] ?? new \DateTime());
                    
                    $manager->persist($portfolio);
                }
            }
        }
    }
}
