<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\DTO;

use WernerDweight\RA\RA;

class SoftDeleteGraph
{
    /** @var RA */
    private $deleteRelations;

    /** @var RA */
    private $deleteEmbedded;

    /** @var RA */
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
     * @return RA
     */
    public function getDeleteRelations(): RA
    {
        return $this->deleteRelations;
    }

    /**
     * @return RA
     */
    public function getDeleteEmbedded(): RA
    {
        return $this->deleteEmbedded;
    }

    /**
     * @return RA
     */
    public function getDetachRelations(): RA
    {
        return $this->detachRelations;
    }
}
