<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use WernerDweight\DoctrineCascadeSoftDeleteBundle\Service\CascadeSoftDeleter;

class CascadeSoftDeleteListener
{
    /**
     * @var CascadeSoftDeleter
     */
    private $deleter;

    public function __construct(CascadeSoftDeleter $cascadeSoftDeleter)
    {
        $this->deleter = $cascadeSoftDeleter;
    }

    /**
     * @throws \Exception
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->deleter->delete($args->getEntity());
    }
}
