<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository
 */
class EnhancedEntityRepository extends ServiceEntityRepository
{
    private $entityManager = $this->getEntityManager();

    public function editEntity(object $entity, array $data): array
    {
        foreach ($data as $fieldName => $fieldValue) {
            $setter = 'set' . ucfirst($fieldName);
            if (method_exists($entity, $setter)) {
                $entity->$setter($fieldValue);
            }
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return ['message' => 'success'];
    }
}
