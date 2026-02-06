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
    protected $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->entityClass = static::getEntityClass();
        $this->entityManager = $registry->getManager();
        parent::__construct($registry, $this->entityClass);
        

    }

    public function editEntity(object $entity, array $data): array
    {
        
        foreach ($data as $fieldName => $fieldValue) {
            if ($fieldName === 'id') {
                continue; // Skip the ID field
            }
            $setter = 'set' . ucfirst($fieldName);
            if (method_exists($entity, $setter)) {
                $entity->$setter($fieldValue);
            }
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return ['message' => 'success'];
    }

    public function removebyId(int $id): void
    {
        $entity = $this->find($id);
        if ($entity !== null) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }
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
