<?php

namespace Mangopixel\Adjuster\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * This interface describes which methods an adjustable model should have. All of the
 * methods below are satisfied by the CanBeAdjusted trait. It's a good practice to
 * implement this interface when using the trait, but it's completely optional.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
interface Adjustable
{
    /**
     * Adjusts the model by updating an existing record in the adjustments table or adds
     * a new one if no previous adjustments are set. All changes will be merged with
     * old changes and old changes can be unset using null.
     *
     * @param  array $changes
     * @return Model|null
     */
    public function adjust( array $changes );

    /**
     * Get the adjustment associated with the changeable model.
     *
     * @return Relation
     */
    public function adjustment():Relation;

    /**
     * Fill the model instance with the adjusted values, replacing the original values.
     *
     * @return self
     */
    public function applyAdjustments():Model;

    /**
     * Checks if the given model has applied the adjustments.
     *
     * @return bool
     */
    public function isAdjusted():bool;

    /**
     * Checks if save protection is enabled. Save protection protects you from persisting
     * the model after applying the adjustments.
     *
     * @return bool
     */
    public function hasSaveProtection():bool;
}