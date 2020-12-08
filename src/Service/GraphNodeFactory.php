<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Service;

use WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO\SoftDeleteGraphNode;
use WernerDweight\RA\RA;

class GraphNodeFactory
{
    public function create(string $entityClass, string $property, RA $foreignKeys): SoftDeleteGraphNode
    {
        return new SoftDeleteGraphNode($entityClass, $property, $foreignKeys);
    }
}
