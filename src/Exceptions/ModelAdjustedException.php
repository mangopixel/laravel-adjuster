<?php

namespace Mangopixel\Adjuster\Exceptions;

use Exception;

/**
 * An exception which is thrown when trying to call save on a model that has applied
 * adjustments through the applyAdjustments method on the CanBeAdjusted trait.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
class ModelAdjustedException extends Exception
{
    /**
     * Constructor.
     *
     * @param string|null $message
     */
    public function __construct( string $message = null )
    {
        parent::__construct( $message ?: 'You cannot persist a model with applied adjustments and save protection enabled' );
    }
}