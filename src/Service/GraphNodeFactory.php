<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraph;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraphNode;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception\GraphFactoryException;

class GraphNodeFactory
{
    /**
     * @param string $entityClass
     * @param string $property
     * @param RA $foreignKeys
     * @return SoftDeleteGraphNode
     */
    public function create(string $entityClass, string $property, RA $foreignKeys): SoftDeleteGraphNode
    {
        return new SoftDeleteGraphNode($entityClass, $property, $foreignKeys);
    }
}
