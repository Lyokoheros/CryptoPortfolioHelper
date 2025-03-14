<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<User>
 */
class UserRepository extends EnhancedEntityRepository
{
    private $entityManager;
    private CurrencyRepository $currencyRepository;

    public function __construct(ManagerRegistry $registry, CurrencyRepository $currencyRepository)
    {
        parent::__construct($registry);
        $this->currencyRepository = $currencyRepository;
        $this->entityManager = $this->getEntityManager();
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

    public function editUser($userData, $id): array
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
}
