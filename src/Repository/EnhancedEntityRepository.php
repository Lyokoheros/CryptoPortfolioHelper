<?php

namespace App\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use ReflectionClass;
use Stringable;

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
            if (str_contains(strtolower($fieldName), 'date')) 
            {
                $fieldValue = $this->handleDate($fieldValue);
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

    public function handleDate(mixed $date): DateTime
    {
        if(is_string($date) || $date instanceof Stringable)
        {
            $date = new DateTime($date);
        }
        else if(!($date instanceof \DateTimeInterface))
        {
            throw new \RuntimeException(
                'Wrong date format: ' . $date  
                . ' of type ' . gettype($date)
            );
        }
        return $date;           
    }

    protected function addOptionalQueryCriteria(QueryBuilder $queryBuilder , array $criteria): QueryBuilder
    {
        foreach ($criteria as $field => $value) 
        {
            if ($value === null) 
            {
                continue;
            }
            if($field == 'startDate')
            {
                $queryBuilder->andWhere("t.date >= :$field");
                $queryBuilder->setParameter($field, $value);
                continue;
            }
            if($field == 'endDate')
            {
                $queryBuilder->andWhere("t.date <= :$field");
                $queryBuilder->setParameter($field, $value);
                continue;
            }
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

    function removeAll(): void
    {
        $objects = $this->findAll();
        foreach($objects as $object)
        {
            $this->entityManager->remove($object);
        }
        $this->entityManager->flush();
     }
}
