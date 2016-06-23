<?php

namespace Mangopixel\Adjuster;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Mangopixel\Adjuster\Contracts\Adjustable;
use Mangopixel\Adjuster\Exceptions\ModelAdjustedException;

/**
 * This trait is where most of the package logic lives. This trait satisfies the
 * entire Adjustable contract and you should only use the trait on classes that
 * extend Illuminate\Database\Eloquent\Model.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
trait CanBeAdjusted
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
    protected static function bootCanBeAdjusted()
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
     * @param  array $attributes
     * @return Model|null
     */
    public function adjust( array $changes, array $attributes = [ ] )
    {
        $changesColumn = config( 'adjuster.changes_column' );
        $adjustment = $this->adjustment()->exists() ? $this->adjustment : app( 'adjuster.model' );
        $existingChanges = collect( $adjustment->$changesColumn );

        // We will fetch any existing changes from the adjustment and then filter them down
        // based on certain criterias. If the value is null or the adjusted value equals
        // the original value we will remove the value before persisting the changes.
        $changes = $existingChanges->merge( $changes )->filter( function ( $value, $attribute ) {
            return ! is_null( $value ) && $this->$attribute !== $value;
        } );

        if ( $changes->isEmpty() ) {
            $adjustment->delete();
        } else {
            $adjustment->fill( $attributes );
            $adjustment->$changesColumn = $this->castChanges( $changes, $adjustment );
            $this->adjustment()->save( $adjustment );

            return $adjustment;
        }
    }

    /**
     * Get the adjustment associated with the adjustable model.
     *
     * @return Relation
     */
    public function adjustment():Relation
    {
        if ( config( 'adjuster.polymorphic' ) ) {
            return $this->morphOne( config( 'adjuster.adjustment_model' ), config( 'adjuster.adjustable_column' ) );
        } else {
            return $this->hasOne( config( 'adjuster.adjustment_model' ), config( 'adjuster.adjustable_column' ) );
        }
    }

    /**
     * Fill the model instance with the adjusted values, replacing the original values.
     *
     * @return self
     */
    public function applyAdjustments():Model
    {
        $changes = $this->adjustment->{config( 'adjuster.changes_column' )} ?? null;

        if ( is_string( $changes ) ) {
            $changes = json_decode( $changes, true );
        } elseif ( $changes instanceof Collection ) {
            $changes = $changes->toArray();
        }

        $this->fill( $changes );
        $this->adjusted = true;

        return $this;
    }

    /**
     * Checks if the given model has applied the adjustments.
     *
     * @return bool
     */
    public function isAdjusted():bool
    {
        return $this->adjusted;
    }

    /**
     * Checks if save protection is enabled. Save protection protects you from persisting
     * the model after applying the adjustments.
     *
     * @return bool
     */
    public function hasSaveProtection():bool
    {
        return $this->saveProtection ?? config( 'adjuster.save_protection' );
    }

    /**
     * Check if the changes attribute has any set casts on the given model, and if so we
     * cast the changes collection to the appropiate type.
     *
     * @param  Collection $changes
     * @param  Model      $adjustment
     * @return mixed
     */
    protected function castChanges( Collection $changes, Model $adjustment )
    {
        switch ( $adjustment->hasCast( config( 'adjuster.changes_column' ) ) ) {
            case 'collection':
                return $changes;
            case 'array':
            case 'json':
                return $changes->toArray();
            default:
                return $changes->toJson();
        }
    }
}