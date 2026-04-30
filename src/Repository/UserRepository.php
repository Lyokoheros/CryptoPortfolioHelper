<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Portfolio;
use App\Entity\Currency;
use App\Repository\PortfolioRepository;
use App\Repository\CurrencyRepository;
use App\Repository\EnhancedEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<User>
 */
class UserRepository extends EnhancedEntityRepository
{

    public function __construct(
        ManagerRegistry $registry, 
        private CurrencyRepository $currencyRepository,
        private PortfolioRepository $portfolioRepository
    ) {
        parent::__construct($registry);
    }

    public function getAllUsers(): array
    {
        $users = [];
        foreach($this->findAll() as $user)
        {
            $users[$user->getId()] = $user;
        }
        return $users;
    }

    public function registerUser($userData): array
    {
        $user = new User();
        $user->setUserName($userData['userName']);
        $user->setCountry($userData['country']);
        $user->setEMail($userData['eMail']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $mainPortfolio = new Portfolio();
        $mainPortfolio->setUser($user);
        echo $user->getId();

        //to do: hash password and validate data
        $this->editUser($user->getId(), $userData);        
        return ['message' => 'success'];
    }   

    public function editUser($id, $userData): array
    { 
        $user = $this->find($id);

        if(isset($userData['nativeCurrencySymbol']))
        {
            $userData['nativeCurrency']  = $this->currencyRepository->findOneBy(['symbol' => $userData['nativeCurrencySymbol']]);
        }
        if(isset($userData['nativeCurrencyId']))
        {
            $userData['nativeCurrency']  = $this->currencyRepository->find($userData['nativeCurrencyId']);
        }
        if(isset($userData['displayCurrencySymbol']))
        {
            $userData['displayCurrency']  = $this->currencyRepository->findOneBy(['symbol' => $userData['displayCurrencySymbol']]);
        }
        if(isset($userData['displayCurrencyId']))
        {
            $userData['displayCurrency']  = $this->currencyRepository->find($userData['displayCurrencyId']);
        }
        $this->editEntity($user, $userData);

        return ['message succes'];
    }

    public function removeUser($id): array
    {
        $this->remove($this->find($id));
        return ['message' => 'success'];
    }

    public function findUsersDefaultPortfolio(User $user): ?Portfolio
    {
        $userPortfolios = $this->portfolioRepository->findBy(['user' => $user]);

        foreach($userPortfolios as $portfolio)
        {
            if($portfolio->isDefault())
            {
                return $portfolio;
            }
        }
        return null;
    }
}
