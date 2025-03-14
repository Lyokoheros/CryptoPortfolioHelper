<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ReflectionClass;

/**
 * @extends ServiceEntityRepository<object>
 */
class EnhancedEntityRepository extends ServiceEntityRepository
{
    protected $entityClass;

    public function __construct(ManagerRegistry $registry)
    {
        $this->entityClass = static::getEntityClass();
        parent::__construct($registry, $this->entityClass);
    }

    public function editEntity(object $entity, array $data): array
    {
        $entityManager = $this->getEntityManager();

        foreach ($data as $fieldName => $fieldValue) {
            $setter = 'set' . ucfirst($fieldName);
            if (method_exists($entity, $setter)) {
                $entity->$setter($fieldValue);
            }
        }

        $entityManager->persist($entity);
        $entityManager->flush();

        return ['message' => 'success'];
    }

    public static function getEntityClass(): string
    {
        $class = static::class;
        if (!is_subclass_of($class, ServiceEntityRepository::class)) {
            throw new \LogicException('This class should be used with entities that extend ServiceEntityRepository.');
        }

        $entityNamespace = substr($class, 0, strrpos($class, 'Repository'));

        return substr($entityNamespace, strrpos($entityNamespace, '\\') + 1);
    }
}
