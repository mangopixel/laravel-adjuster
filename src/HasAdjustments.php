<?php

namespace Mangopixel\Adjuster;

trait HasAdjustments
{
    /**
     * A boolean to keep track of wether the model has been adjusted or not.
     *
     * @var bool
     */
    protected $adjusted = false;

    /**
     * The booting method of the model trait. This method will be called once the model
     * has been booted, and registers an event listener that listens for save calls
     * to block if save protection is enabled.
     *
     * @return void
     * @throws ModelAdjustedException
     */
    protected static function bootHasAdjustments()
    {
        static::saving( function ( Adjustable $model ) {
            if ( $model->isAdjusted() && $model->hasSaveProtection() ) {
                throw new ModelAdjustedException();
            }
        } );
    }

    /**
     * Adjusts the model by updating an existing record in the adjustments table or adds
     * a new one if no previous adjustments are set. All changes will be merged with
     * old changes and old changes can be unset using null.
     *
     * @param  array $changes
     * @return Adjustment
     */
    public function adjust( array $changes )
    {
        $adjustment = Adjustment::firstOrNew( [
            config( 'adjuster.adjustable_column' ) . '_id'   => $this->{$this->getKeyName()},
            config( 'adjuster.adjustable_column' ) . '_type' => $this->getMorphClass()
        ] );

        $oldChanges = collect( $adjustment->{config( 'adjuster.changes_column' )} );
        $changes = $oldChanges->merge( $changes )->filter( function ( $value, $key ) {
            return ! is_null( $value ) && $this->$key !== $value;
        } );

        if ( $changes->isEmpty() ) {
            $adjustment->delete();
        } else {
            $adjustment->{config( 'adjuster.changes_column' )} = $changes->toJson();
            $adjustment->save();
        }

        return $adjustment;
    }

    /**
     * Fill the model instance with the adjusted values, replacing the original values.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function applyAdjustments()
    {
        $changes = $this->adjustment->{config( 'adjuster.changes_column' )} ?? null;

        if ( is_null( $changes ) ) {
            return;
        }

        $this->fill( $changes );

        $this->adjusted = true;
    }

    /**
     * Checks if the given model has applied the adjustments.
     *
     * @return bool
     */
    public function isAdjusted()
    {
        return $this->adjusted;
    }

    /**
     * Checks if save protection is enabled. Save protection protects you from persisting
     * the model after applying the adjustments.
     *
     * @return bool
     */
    public function hasSaveProtection()
    {
        return $this->saveProtection ?? config( 'adjuster.save_protection' );
    }

    /**
     * Get the adjustment associated with the adjustable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adjustment()
    {
        return $this->morphOne( config( 'adjuster.adjustment_model' ), config( 'adjuster.adjustable_column' ) );
    }
}