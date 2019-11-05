<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraphNode;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception\GraphFetcherException;

class GraphFetcher
{
    /** @var string */
    private const MODE_CASCADE = 'CASCADE';
    /** @var string */
    private const MODE_SET_NULL = 'SET NULL';
    /** @var string */
    private const JOIN_COLUMNS_PROPERTY = 'joinColumns';
    /** @var string */
    private const INVERSE_JOIN_COLUMNS_PROPERTY = 'inverseJoinColumns';
    /** @var string */
    private const JOIN_TABLE_PROPERTY = 'joinTable';
    /** @var string */
    private const ON_DELETE_ATTRIBUTE = 'onDelete';
    /** @var string */
    private const SOURCE_ENTITY_ATTRIBUTE = 'sourceEntity';
    /** @var string */
    private const FIELD_NAME_ATTRIBUTE = 'fieldName';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var GraphFactory */
    private $graphFactory;

    /** @var ClassMetadata[] */
    private $metadata;

    /**
     * GraphFetcher constructor.
     * @param EntityManagerInterface $entityManager
     * @param GraphFactory $graphFactory
     */
    public function __construct(EntityManagerInterface $entityManager, GraphFactory $graphFactory)
    {
        $this->entityManager = $entityManager;
        $this->graphFactory = $graphFactory;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array $ids
     * @return array
     */
    private function getPrimaryKeysToDeleteAssociationsBy(string $className, string $fieldName, array $ids): array
    {
        $keysArray = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(this)')
            ->from($className, 'this')
            ->where('IDENTITY(this.' . $fieldName . ') IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        $keys = [];
        foreach ($keysArray as $key) {
            $keys[] = $key['id'];
        }
        return $keys;
    }

    /**
     * @param array $ids
     * @param array $joinColumn
     * @param array $association
     * @return GraphFetcher
     */
    private function processJoinColumn(array $ids, array $joinColumn, array $association): self
    {
        if ($joinColumn[self::ON_DELETE_ATTRIBUTE] === self::MODE_CASCADE) {
            $this->graphFactory->pushRelationToDelete(
                $association[self::SOURCE_ENTITY_ATTRIBUTE],
                $association[self::FIELD_NAME_ATTRIBUTE],
                $ids
            );
            $this->fetchDeleteGraph(
                $association[self::SOURCE_ENTITY_ATTRIBUTE],
                $this->getPrimaryKeysToDeleteAssociationsBy(
                    $association[self::SOURCE_ENTITY_ATTRIBUTE],
                    $association[self::FIELD_NAME_ATTRIBUTE],
                    $ids
                )
            );
        } elseif ($joinColumn[self::ON_DELETE_ATTRIBUTE] === self::MODE_SET_NULL) {
            $this->graphFactory->pushRelationToDetach(
                $association[self::SOURCE_ENTITY_ATTRIBUTE],
                $association[self::FIELD_NAME_ATTRIBUTE],
                $ids
            );
        }
        return $this;
    }

    /**
     * @param array $ids
     * @param array $association
     * @return GraphFetcher
     */
    private function processAssociation(array $ids, array $association): self
    {
        if (true === array_key_exists(self::JOIN_COLUMNS_PROPERTY, $association)) {
            foreach ($association[self::JOIN_COLUMNS_PROPERTY] as $joinColumn) {
                if (true === array_key_exists(self::ON_DELETE_ATTRIBUTE, $joinColumn)) {
                    $this->processJoinColumn($ids, $joinColumn, $association);
                }
            }
            return $this;
        }
        if (true === array_key_exists(self::JOIN_TABLE_PROPERTY, $association)) {
            if (true === array_key_exists(self::INVERSE_JOIN_COLUMNS_PROPERTY, $association[self::JOIN_TABLE_PROPERTY])) {
                // pure M:N relations can't be processed without deleting entries (no longer soft delete)
                throw new GraphFetcherException(
                    GraphFetcherException::INVALID_SCHEMA,
                    [$association[self::JOIN_TABLE_PROPERTY]['name']]
                );
            }
        }
        return $this;
    }

    /**
     * @param array $ids
     * @param array $associations
     * @return GraphFetcher
     */
    private function processAssociations(array $ids, array $associations): self
    {
        if (true !== empty($associations)) {
            foreach ($associations as $association) {
                $this->processAssociation($ids, $association);
            }
        }
        return $this;
    }

    /**
     * @param array $ids
     * @param string $entityClass
     * @param array $embeddedClasses
     * @return CascadeSoftDeleter
     */
    private function processEmbeddedClasses(array $ids, string $entityClass, array $embeddedClasses): self
    {
        if (true !== empty($embeddedClasses)) {
            foreach ($embeddedClasses as $property => $embeddedClass) {
                $this->graphFactory->pushEmbeddedToDelete($entityClass, $property, $ids);
            }
        }
        return $this;
    }

    /**
     * @return ClassMetadata[[
     */
    private function fetchMetadata(): array
    {
        if ($this->metadata === null) {
            /** @var ClassMetadata[] $metadata */
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $this->metadata = $metadata;
        }
        return $this->metadata;
    }

    /**
     * @param string $entityClass
     * @param array $ids
     * @return CascadeSoftDeleter
     */
    public function fetchDeleteGraph(string $entityClass, array $ids): SoftDeleteGraph
    {
        $this->graphFactory->initialize();
        $entityMetadata = $this->entityManager->getClassMetadata($entityClass);
        $parentClasses = $entityMetadata->parentClasses;

        foreach ($this->fetchMetadata() as $mapping) {
            $associations = $mapping->getAssociationsByTargetClass($entityClass);
            $this->processAssociations($ids, $associations);

            if (true !== empty($parentClasses)) {
                foreach ($parentClasses as $parentClass) {
                    $associations = $mapping->getAssociationsByTargetClass($parentClass);
                    $this->processAssociations($ids, $associations);
                }
            }
        }

        $embeddedClasses = $entityMetadata->embeddedClasses;
        $this->processEmbeddedClasses($ids, $entityClass, $embeddedClasses);

        return $this->graphFactory->eject();
    }
}
