<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EnhancedEntityRepository<User>
 */
class UserRepository extends EnhancedEntityRepository
{
    private $entityManager = $this->getEntityManager();
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
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
        $user = new User();
        foreach($userData as $fieldName => $fieldValue)
        {
            if($user->hasField($fieldName))
            {
                $methodName = 'set' . ucfirst($fieldName);
                $user->$methodName($fieldValue);
            }
        }
        return ['message succes'];
    }
}
