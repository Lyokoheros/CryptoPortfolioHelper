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
                    'date' => '2021-07-05'
                ]
            ]
                        
        ]
    ];

    public function getDependencies():array
    {
        return [
            UserFixtures::class
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
            $portfolio->setBatchSize(1);
            $portfolio->setStartingDate(new \DateTime());
            $portfolio->setName('Main portfolio');

            $manager->persist($portfolio);
            if (isset(self::USERS_PORTFOLIO_DATA[$user->getUserName()]))
            {
                foreach(self::USERS_PORTFOLIO_DATA[$user->getUserName()] as $portfolioData)
                {
                    $portfolio = new Portfolio();
                    $portfolio->setUser($user);
                    $portfolio->setName($portfolioData['name'] ?? 'unknown');
                    $portfolio->setBatchSize($portfolioData['batchSize'] ?? 1);
                    $portfolio->setStartingDate(new \DateTime($portfolioData['date'] ?? ''));
                    
                    $manager->persist($portfolio);
                }
            }
        }
    }
}
