<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class GraphFactoryException extends AbstractEnhancedException
{
    /** @var int */
    public const GRAPH_ALREADY_PRESENT = 1;
    /** @var int */
    public const NO_GRAPH_INITIALIZED = 2;

    /** @var string[] */
    protected static $messages = [
        self::GRAPH_ALREADY_PRESENT => 'A graph object is already being prepared! Did you forget to eject the graph?',
        self::NO_GRAPH_INITIALIZED => 'No graph object has been initialized yet! Did you forget to call initialize?',
    ];
}
