<?php

namespace Mangopixel\Adjuster;

interface Adjustable
{
    /**
     * Adjusts the model by updating an existing record in the adjustments table or adds
     * a new one if no previous adjustments are set. All changes will be merged with
     * old changes and old changes can be unset using null.
     *
     * @param  array $changes
     * @return Adjustment
     */
    public function adjust( array $changes );

    /**
     * Fill the model instance with the adjusted values, replacing the original values.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function applyAdjustments();

    /**
     * Checks if the given model has applied the adjustments.
     *
     * @return bool
     */
    public function isAdjusted();

    /**
     * Checks if save protection is enabled. Save protection protects you from persisting
     * the model after applying the adjustments.
     *
     * @return bool
     */
    public function hasSaveProtection();

    /**
     * Get the adjustment associated with the changeable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adjustment();
}