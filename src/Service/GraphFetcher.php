<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception\GraphFetcherException;
use WernerDweight\RA\RA;

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

    /** @var ClassMetadata[]|null */
    private $metadata;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var GraphFactory */
    private $graphFactory;

    /**
     * GraphFetcher constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, GraphFactory $graphFactory)
    {
        $this->entityManager = $entityManager;
        $this->graphFactory = $graphFactory;
    }

    private function getPrimaryKeysToDeleteAssociationsBy(string $className, string $fieldName, RA $ids): RA
    {
        $keysArray = $this->entityManager->createQueryBuilder()
            ->select('this.id')
            ->from($className, 'this')
            ->where('IDENTITY(this.' . $fieldName . ') IN (:ids)')
            ->setParameter('ids', $ids->toArray())
            ->getQuery()
            ->getArrayResult();

        return (new RA($keysArray))->map(function (array $entry) {
            return $entry['id'];
        });
    }

    /**
     * @param string[] $joinColumn
     * @param mixed[]  $association
     *
     * @return GraphFetcher
     */
    private function processJoinColumn(RA $ids, array $joinColumn, array $association): self
    {
        if (self::MODE_CASCADE === $joinColumn[self::ON_DELETE_ATTRIBUTE]) {
            $this->graphFactory->pushRelationToDelete(
                $association[self::SOURCE_ENTITY_ATTRIBUTE],
                $association[self::FIELD_NAME_ATTRIBUTE],
                $ids
            );
            $associationKeys = $this->getPrimaryKeysToDeleteAssociationsBy(
                $association[self::SOURCE_ENTITY_ATTRIBUTE],
                $association[self::FIELD_NAME_ATTRIBUTE],
                $ids
            );
            if ($associationKeys->length() > 0) {
                $this->fetchDeleteGraph($association[self::SOURCE_ENTITY_ATTRIBUTE], $associationKeys);
            }
        } elseif (self::MODE_SET_NULL === $joinColumn[self::ON_DELETE_ATTRIBUTE]) {
            $this->graphFactory->pushRelationToDetach(
                $association[self::SOURCE_ENTITY_ATTRIBUTE],
                $association[self::FIELD_NAME_ATTRIBUTE],
                $ids
            );
        }
        return $this;
    }

    /**
     * @param mixed[] $association
     *
     * @return GraphFetcher
     */
    private function processAssociation(RA $ids, array $association): self
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
            if (true === array_key_exists(
                self::INVERSE_JOIN_COLUMNS_PROPERTY,
                $association[self::JOIN_TABLE_PROPERTY]
            )) {
                // pure M:N relations can't be processed without deleting entries (no longer soft delete)
                throw new GraphFetcherException(GraphFetcherException::INVALID_SCHEMA, [$association[self::JOIN_TABLE_PROPERTY]['name']]);
            }
        }
        return $this;
    }

    /**
     * @param array[] $associations
     *
     * @return GraphFetcher
     */
    private function processAssociations(RA $ids, array $associations): self
    {
        if (true !== empty($associations)) {
            foreach ($associations as $association) {
                $this->processAssociation($ids, $association);
            }
        }
        return $this;
    }

    /**
     * @param string[] $embeddedClasses
     *
     * @return GraphFetcher
     */
    private function processEmbeddedClasses(RA $ids, string $entityClass, array $embeddedClasses): self
    {
        if (true !== empty($embeddedClasses)) {
            /** @var string $property */
            foreach (array_keys($embeddedClasses) as $property) {
                $this->graphFactory->pushEmbeddedToDelete($entityClass, $property, $ids);
            }
        }
        return $this;
    }

    /**
     * @return ClassMetadata[]
     */
    private function fetchMetadata(): array
    {
        if (null === $this->metadata) {
            /** @var ClassMetadata[] $metadata */
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $this->metadata = $metadata;
        }
        return $this->metadata;
    }

    /**
     * @return GraphFetcher
     */
    public function fetchDeleteGraph(string $entityClass, RA $ids): self
    {
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

        return $this;
    }
}
