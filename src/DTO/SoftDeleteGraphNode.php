<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO;

use WernerDweight\RA\RA;

class SoftDeleteGraphNode
{
    /** @var string */
    protected $entityClass;

    /** @var string */
    protected $property;

    /** @var RA */
    protected $foreignKeys;

    /**
     * SoftDeleteGraphNode constructor.
     */
    public function __construct(string $entityClass, string $property, RA $foreignKeys)
    {
        $this->entityClass = $entityClass;
        $this->property = $property;
        $this->foreignKeys = $foreignKeys;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getForeignKeys(): RA
    {
        return $this->foreignKeys;
    }
}
