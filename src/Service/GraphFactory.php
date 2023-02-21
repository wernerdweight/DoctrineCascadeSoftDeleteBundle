<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraph;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception\GraphFactoryException;
use WernerDweight\RA\RA;

class GraphFactory
{
    /**
     * @var GraphNodeFactory
     */
    private $graphNodeFactory;

    /**
     * @var SoftDeleteGraph|null
     */
    private $graph;

    public function __construct(GraphNodeFactory $graphNodeFactory)
    {
        $this->graphNodeFactory = $graphNodeFactory;
    }

    public function initialize(): void
    {
        if (null !== $this->graph) {
            throw new GraphFactoryException(GraphFactoryException::GRAPH_ALREADY_PRESENT);
        }
        $this->graph = new SoftDeleteGraph();
    }

    public function pushRelationToDelete(string $entityClass, string $property, RA $foreignKeys): void
    {
        $this->getGraph()
            ->getDeleteRelations()
            ->push(
                $this->graphNodeFactory->create($entityClass, $property, $foreignKeys)
            );
    }

    public function pushEmbeddedToDelete(string $entityClass, string $property, RA $foreignKeys): void
    {
        $this->getGraph()
            ->getDeleteEmbedded()
            ->push(
                $this->graphNodeFactory->create($entityClass, $property, $foreignKeys)
            );
    }

    public function pushRelationToDetach(string $entityClass, string $property, RA $foreignKeys): void
    {
        $this->getGraph()
            ->getDetachRelations()
            ->push(
                $this->graphNodeFactory->create($entityClass, $property, $foreignKeys)
            );
    }

    public function eject(): SoftDeleteGraph
    {
        $graph = $this->getGraph();
        $this->graph = null;
        return $graph;
    }

    private function getGraph(): SoftDeleteGraph
    {
        if (null === $this->graph) {
            throw new GraphFactoryException(GraphFactoryException::NO_GRAPH_INITIALIZED);
        }
        return $this->graph;
    }
}
