<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker;
use Safe\DateTime;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraph;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraphNode;
use WernerDweight\RA\RA;

class CascadeSoftDeleter
{
    /**
     * @var string
     */
    private const DOCTRINE_PROXY_PREFIX = 'Proxies\\__CG__\\';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var GraphFetcher
     */
    private $graphFetcher;

    /**
     * @var GraphFactory
     */
    private $graphFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        GraphFetcher $graphFetcher,
        GraphFactory $graphFactory
    ) {
        $this->entityManager = $entityManager;
        $this->graphFetcher = $graphFetcher;
        $this->graphFactory = $graphFactory;
    }

    /**
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function delete(object $entity): void
    {
        $className = $this->getProxylessClass($entity);
        $entityMetadata = $this->entityManager->getClassMetadata($className);
        $identifierFieldName = $entityMetadata->getSingleIdentifierFieldName();
        $this->graphFactory->initialize();
        $this->graphFetcher->fetchDeleteGraph(
            $className,
            new RA([$entity->{'get' . ucfirst($identifierFieldName)}()])
        );
        $graph = $this->graphFactory->eject();
        $this->executeDelete($graph);
    }

    private function getProxylessClass(object $entity): string
    {
        return str_replace(self::DOCTRINE_PROXY_PREFIX, '', get_class($entity));
    }

    /**
     * @throws \Exception
     */
    private function executeDelete(SoftDeleteGraph $graph): self
    {
        /** @var SoftDeleteGraphNode $detachRelation */
        foreach ($graph->getDetachRelations() as $detachRelation) {
            $this->entityManager->createQueryBuilder()
                ->update($detachRelation->getEntityClass(), 'this')
                ->set('this.' . $detachRelation->getProperty(), ':id')
                ->setParameter('id', null)
                ->where('IDENTITY(this.' . $detachRelation->getProperty() . ') IN (:ids)')
                ->setParameter('ids', $detachRelation->getForeignKeys()->toArray())
                ->getQuery()
                ->execute();
        }
        /** @var SoftDeleteGraphNode $deleteEmbedded */
        foreach ($graph->getDeleteEmbedded() as $deleteEmbedded) {
            $this->entityManager->createQueryBuilder()
                ->update($deleteEmbedded->getEntityClass(), 'this')
                ->set('this.' . $deleteEmbedded->getProperty() . '.deletedAt', ':now')
                ->setParameter('now', new DateTime())
                ->where('this.id IN (:ids)')
                ->setParameter('ids', $deleteEmbedded->getForeignKeys()->toArray())
                ->getQuery()
                ->execute();
        }
        /** @var SoftDeleteGraphNode $deleteRelation */
        foreach ($graph->getDeleteRelations() as $deleteRelation) {
            $this->entityManager->createQueryBuilder()
                ->delete($deleteRelation->getEntityClass(), 'this')
                ->where('IDENTITY(this.' . $deleteRelation->getProperty() . ') IN (:ids)')
                ->setParameter('ids', $deleteRelation->getForeignKeys()->toArray())
                ->getQuery()
                ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SoftDeleteableWalker::class)
                ->execute();
        }

        return $this;
    }
}
