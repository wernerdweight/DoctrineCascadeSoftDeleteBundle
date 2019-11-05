<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraphNode;

class GraphNodeFactory
{
    /**
     * @param string $entityClass
     * @param string $property
     * @param RA     $foreignKeys
     *
     * @return SoftDeleteGraphNode
     */
    public function create(string $entityClass, string $property, RA $foreignKeys): SoftDeleteGraphNode
    {
        return new SoftDeleteGraphNode($entityClass, $property, $foreignKeys);
    }
}
