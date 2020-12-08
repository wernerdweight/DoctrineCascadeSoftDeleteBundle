<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraph;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception\GraphFactoryException;
use WernerDweight\RA\RA;

class GraphFactory
{
    /** @var GraphNodeFactory */
    private $graphNodeFactory;

    /** @var SoftDeleteGraph|null */
    private $graph;

    /**
     * GraphFactory constructor.
     */
    public function __construct(GraphNodeFactory $graphNodeFactory)
    {
        $this->graphNodeFactory = $graphNodeFactory;
    }

    private function getGraph(): SoftDeleteGraph
    {
        if (null === $this->graph) {
            throw new GraphFactoryException(GraphFactoryException::NO_GRAPH_INITIALIZED);
        }
        return $this->graph;
    }

    /**
     * @return GraphFactory
     */
    public function initialize(): self
    {
        if (null !== $this->graph) {
            throw new GraphFactoryException(GraphFactoryException::GRAPH_ALREADY_PRESENT);
        }
        $this->graph = new SoftDeleteGraph();
        return $this;
    }

    /**
     * @return GraphFactory
     */
    public function pushRelationToDelete(string $entityClass, string $property, RA $foreignKeys): self
    {
        $this->getGraph()->getDeleteRelations()->push(
            $this->graphNodeFactory->create($entityClass, $property, $foreignKeys)
        );
        return $this;
    }

    /**
     * @return GraphFactory
     */
    public function pushEmbeddedToDelete(string $entityClass, string $property, RA $foreignKeys): self
    {
        $this->getGraph()->getDeleteEmbedded()->push(
            $this->graphNodeFactory->create($entityClass, $property, $foreignKeys)
        );
        return $this;
    }

    /**
     * @return GraphFactory
     */
    public function pushRelationToDetach(string $entityClass, string $property, RA $foreignKeys): self
    {
        $this->getGraph()->getDetachRelations()->push(
            $this->graphNodeFactory->create($entityClass, $property, $foreignKeys)
        );
        return $this;
    }

    public function eject(): SoftDeleteGraph
    {
        $graph = $this->getGraph();
        $this->graph = null;
        return $graph;
    }
}
