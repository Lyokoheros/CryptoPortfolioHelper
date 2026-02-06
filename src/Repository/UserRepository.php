<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Portfolio;
use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use App\Repository\EnhancedEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<User>
 */
class UserRepository extends EnhancedEntityRepository
{
    private CurrencyRepository $currencyRepository;

    public function __construct(ManagerRegistry $registry, CurrencyRepository $currencyRepository)
    {
        parent::__construct($registry);
        $this->currencyRepository = $currencyRepository;
    }

    public function getAllUsers(): array
    {
        $users = [];
        foreach($this->findAll() as $user)
        {
            $users[$user->getId()] = $user->getUserName();
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

        if(isset($userData['nativeCurrency']))
        {
            $userData['nativeCurrency']  = $this->currencyRepository->findOneBy(['symbol' => $userData['nativeCurrency']]);
        }
        if(isset($userData['displayCurrency']))
        {
            $userData['displayCurrency']  = $this->currencyRepository->findOneBy(['symbol' => $userData['displayCurrency']]);
        }
        $this->editEntity($user, $userData);

        return ['message succes'];
    }

    public function removeUser($id): array
    {
        $this->remove($this->find($id));
        return ['message' => 'success'];
    }
}
