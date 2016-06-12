<?php

namespace Mangopixel\Adjuster;

use Exception;

class ModelAdjustedException extends Exception
{
    /**
     * Constructor.
     *
     * @param string $message
     */
    public function __construct( $message = null )
    {
        parent::__construct( $message ?: 'You cannot persist a model with applied adjustments and save protection enabled' );
    }
}