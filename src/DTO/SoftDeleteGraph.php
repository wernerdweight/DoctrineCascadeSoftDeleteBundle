<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO;

use WernerDweight\RA\RA;

class SoftDeleteGraph
{
    /** @var RA<SoftDeleteGraphNode> */
    private $deleteRelations;

    /** @var RA<SoftDeleteGraphNode> */
    private $deleteEmbedded;

    /** @var RA<SoftDeleteGraphNode> */
    private $detachRelations;

    /**
     * SoftDeleteGraph constructor.
     */
    public function __construct()
    {
        $this->deleteRelations = new RA();
        $this->deleteEmbedded = new RA();
        $this->detachRelations = new RA();
    }

    /**
     * @return RA<SoftDeleteGraphNode>
     */
    public function getDeleteRelations(): RA
    {
        return $this->deleteRelations;
    }

    /**
     * @return RA<SoftDeleteGraphNode>
     */
    public function getDeleteEmbedded(): RA
    {
        return $this->deleteEmbedded;
    }

    /**
     * @return RA<SoftDeleteGraphNode>
     */
    public function getDetachRelations(): RA
    {
        return $this->detachRelations;
    }
}
