<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ReflectionClass;

/**
 * @extends ServiceEntityRepository<object>
 */
abstract class EnhancedEntityRepository extends ServiceEntityRepository
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

    /*protected static function getEntityClass(): string
    {
        $class = static::class;

        $entityNamespace = substr($class, 0, strrpos($class, 'Repository'));

        return substr($entityNamespace, strrpos($entityNamespace, '\\') + 1);
    }*/

    protected static function getEntityClass(): string
    {
        $class = static::class;
        $repositoryNamespace = substr($class, 0, strrpos($class, 'Repository'));
        $entityClass = substr($repositoryNamespace, strrpos($repositoryNamespace, '\\') + 1);

        $entityNamespace = "App\\Entity\\" . $entityClass;

        if (!class_exists($entityNamespace)) {
            throw new \LogicException('Entity class does not exist: ' . $entityNamespace);
        }

        return $entityNamespace;
    }
}
