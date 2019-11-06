<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraph;
use WernerDweight\RA\RA;

class CascadeSoftDeleter
{
    /** @var string */
    private const DOCTRINE_PROXY_PREFIX = 'Proxies\\__CG__\\';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var GraphFetcher */
    private $graphFetcher;

    /**
     * CascadeSoftDeleter constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param GraphFetcher           $graphFetcher
     */
    public function __construct(EntityManagerInterface $entityManager, GraphFetcher $graphFetcher)
    {
        $this->entityManager = $entityManager;
        $this->graphFetcher = $graphFetcher;
    }

    /**
     * @param object $entity
     *
     * @return string
     */
    private function getProxylessClass(object $entity): string
    {
        return str_replace(self::DOCTRINE_PROXY_PREFIX, '', get_class($entity));
    }

    /**
     * @param SoftDeleteGraph $graph
     *
     * @return CascadeSoftDeleter
     *
     * @throws \Exception
     */
    private function executeDelete(SoftDeleteGraph $graph): self
    {
        foreach ($graph->getDetachRelations() as $detachRelation) {
            $this->entityManager->createQueryBuilder()
                ->update($detachRelation->getEntityClass(), 'this')
                ->set('this.' . $detachRelation->getProperty(), ':id')
                ->setParameter('id', null)
                ->where('IDENTITY(this.' . $detachRelation->getProperty() . ') IN (:ids)')
                ->setParameter('ids', $detachRelation->getForeignKeys())
                ->getQuery()
                ->execute();
        }
        foreach ($graph->getDeleteEmbedded() as $deleteEmbedded) {
            $this->entityManager->createQueryBuilder()
                ->update($deleteEmbedded->getEntityClass(), 'this')
                ->set('this.' . $deleteEmbedded->getProperty() . '.deletedAt', ':now')
                ->setParameter('now', new \DateTime())
                ->where('this.id IN (:ids)')
                ->setParameter('ids', $deleteEmbedded->getForeignKeys())
                ->getQuery()
                ->execute();
        }
        foreach ($graph->getDetachRelations() as $deleteRelation) {
            $this->entityManager->createQueryBuilder()
                ->delete($deleteRelation->getEntityClass(), 'this')
                ->where('IDENTITY(this.' . $deleteRelation->getProperty() . ') IN (:ids)')
                ->setParameter('ids', $deleteRelation->getForeignKeys())
                ->getQuery()
                ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SoftDeleteableWalker::class)
                ->execute();
        }

        return $this;
    }

    /**
     * @param object $entity
     *
     * @return CascadeSoftDeleter
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function delete(object $entity): self
    {
        $className = $this->getProxylessClass($entity);
        $entityMetadata = $this->entityManager->getClassMetadata($className);
        $identifierFieldName = $entityMetadata->getSingleIdentifierFieldName();
        $graph = $this->graphFetcher->fetchDeleteGraph(
            $className,
            new RA([$entity->{'get' . ucfirst($identifierFieldName)}()])
        );
        $this->executeDelete($graph);
        return $this;
    }
}
