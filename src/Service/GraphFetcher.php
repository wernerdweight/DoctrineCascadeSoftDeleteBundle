<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception\GraphFetcherException;
use WernerDweight\RA\RA;

class GraphFetcher
{
    /**
     * @var string
     */
    private const MODE_CASCADE = 'CASCADE';

    /**
     * @var string
     */
    private const MODE_SET_NULL = 'SET NULL';

    /**
     * @var string
     */
    private const JOIN_COLUMNS_PROPERTY = 'joinColumns';

    /**
     * @var string
     */
    private const INVERSE_JOIN_COLUMNS_PROPERTY = 'inverseJoinColumns';

    /**
     * @var string
     */
    private const JOIN_TABLE_PROPERTY = 'joinTable';

    /**
     * @var string
     */
    private const ON_DELETE_ATTRIBUTE = 'onDelete';

    /**
     * @var string
     */
    private const SOURCE_ENTITY_ATTRIBUTE = 'sourceEntity';

    /**
     * @var string
     */
    private const FIELD_NAME_ATTRIBUTE = 'fieldName';

    /**
     * @var ClassMetadata[]|null
     */
    private $metadata;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var GraphFactory
     */
    private $graphFactory;

    public function __construct(EntityManagerInterface $entityManager, GraphFactory $graphFactory)
    {
        $this->entityManager = $entityManager;
        $this->graphFactory = $graphFactory;
    }

    public function fetchDeleteGraph(string $entityClass, RA $ids): void
    {
        $entityMetadata = $this->entityManager->getClassMetadata($entityClass);
        $parentClasses = $entityMetadata->parentClasses;

        foreach ($this->fetchMetadata() as $mapping) {
            $associations = $mapping->getAssociationsByTargetClass($entityClass);
            $this->processAssociations($ids, $associations);

            if (count($parentClasses) > 0) {
                foreach ($parentClasses as $parentClass) {
                    $associations = $mapping->getAssociationsByTargetClass($parentClass);
                    $this->processAssociations($ids, $associations);
                }
            }
        }

        $embeddedClasses = $entityMetadata->embeddedClasses;
        $this->processEmbeddedClasses($ids, $entityClass, $embeddedClasses);
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
     * @param string[] $association
     */
    private function processJoinColumn(RA $ids, array $joinColumn, array $association): void
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
    }

    /**
     * @param mixed[] $association
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
                throw new GraphFetcherException(GraphFetcherException::INVALID_SCHEMA, [
                    $association[self::JOIN_TABLE_PROPERTY]['name'],
                ]);
            }
        }
        return $this;
    }

    /**
     * @param mixed[][] $associations
     */
    private function processAssociations(RA $ids, array $associations): void
    {
        if (count($associations) > 0) {
            foreach ($associations as $association) {
                $this->processAssociation($ids, $association);
            }
        }
    }

    /**
     * @param array<string, mixed[]> $embeddedClasses
     */
    private function processEmbeddedClasses(RA $ids, string $entityClass, array $embeddedClasses): void
    {
        if (count($embeddedClasses) > 0) {
            /** @var string $property */
            foreach (array_keys($embeddedClasses) as $property) {
                $this->graphFactory->pushEmbeddedToDelete($entityClass, $property, $ids);
            }
        }
    }

    /**
     * @return ClassMetadata[]
     */
    private function fetchMetadata(): array
    {
        if (null === $this->metadata) {
            $metadataFactory = $this->entityManager->getMetadataFactory();
            /** @var ClassMetadata[] $metadata */
            $metadata = $metadataFactory->getAllMetadata();
            $this->metadata = $metadata;
        }
        return $this->metadata;
    }
}
