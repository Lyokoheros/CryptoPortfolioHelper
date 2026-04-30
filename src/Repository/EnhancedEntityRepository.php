<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function saveEntity(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
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

        $this->saveEntity($entity);

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

    protected function addOptionalQueryCriteria(QueryBuilder $queryBuilder , array $criteria): QueryBuilder
    {
        foreach ($criteria as $field => $value) 
        {
            if ($value === null) 
            {
                continue;
            }
            if($field == 'starDate')
            {
                $queryBuilder->andWhere("t.date >= :$field");
                $queryBuilder->setParameter('t.date', $value);
                continue;
            }
            if($field == 'endDate')
            {
                $queryBuilder->andWhere("t.date <= :$field");
                $queryBuilder->setParameter('t.date', $value);
                continue;
            }
            $queryBuilder->setParameter($field, $value);
            // if someone filters on an association, compare its id
            if (isset($this->associationFields[$field]))
            {
                $queryBuilder->andWhere("IDENTITY(t.$field) = :$field");
            }
            else
            {
                $queryBuilder->andWhere("t.$field = :$field");
            }
            $queryBuilder->setParameter($field, $value);
        }
        return $queryBuilder;
    }
}
