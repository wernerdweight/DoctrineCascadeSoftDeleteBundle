<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCascadeSoftDeleteBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class GraphFetcherException extends AbstractEnhancedException
{
    /**
     * @var int
     */
    public const INVALID_SCHEMA = 1;

    /**
     * @var string[]
     */
    protected static $messages = [
        self::INVALID_SCHEMA => 'Invalid schema! Table `%s` needs to be refactored to a mapped entity with two separate N:1 relations' .
            'to be able to cascade soft delete!',
    ];
}
