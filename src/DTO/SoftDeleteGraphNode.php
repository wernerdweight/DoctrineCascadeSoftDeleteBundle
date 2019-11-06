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
     *
     * @param string $entityClass
     * @param string $property
     * @param RA     $foreignKeys
     */
    public function __construct(string $entityClass, string $property, RA $foreignKeys)
    {
        $this->entityClass = $entityClass;
        $this->property = $property;
        $this->foreignKeys = $foreignKeys;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }

    /**
     * @return RA
     */
    public function getForeignKeys(): RA
    {
        return $this->foreignKeys;
    }
}
