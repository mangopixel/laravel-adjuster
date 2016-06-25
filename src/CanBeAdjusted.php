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
        $adjustment = $this->adjustment()->exists() ? $this->adjustment : app( 'adjuster.model' );
        $changes = $this->mergeAndFilterChanges( $changes, $adjustment );

        if ( $changes->isEmpty() ) {
            $adjustment->delete();
        } else {
            $adjustment->fill( $attributes );
            $adjustment->{config( 'adjuster.changes_column' )} = $this->castChanges( $changes, $adjustment );
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
     * @return $this
     */
    public function applyAdjustments():Model
    {
        $changes = $this->adjustment->{config( 'adjuster.changes_column' )} ?? null;

        if ( is_null( $changes ) ) {
            return $this;
        } elseif ( is_string( $changes ) ) {
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
     * We will fetch any existing changes from the adjustment and then filter them down
     * based on certain criterias. Then we will return the changes converted to the
     * correct data type depending on set casts on the Adjustment model.
     *
     * @param  arrray $changes
     * @param  Model  $adjustment
     * @return mixed
     */
    protected function mergeAndFilterChanges( array $changes, Model $adjustment )
    {
        $existingChanges = collect( $adjustment->{config( 'adjuster.changes_column' )} );

        return $existingChanges->merge( $changes )->filter( function ( $value, $attribute ) {
            return ! is_null( $value ) && $this->$attribute !== $value;
        } );
    }

    /**
     * Cast the changes collection to the appropiate type.
     *
     * @param  Collection $changes
     * @param  Model      $adjustment
     * @return mixed
     */
    protected function castChanges( Collection $changes, Model $adjustment )
    {
        $cast = $adjustment->hasCast( config( 'adjuster.changes_column' ) );

        switch ( $cast ) {
            case 'collection':
                return $changes;
            case 'array':
            case 'json':
                return $changes->toArray();
            default:
                return $changes->toJson();
        }
    }

    /**
     * Fill the model with an array of attributes.
     */
    abstract public function fill( array $attributes );

    /**
     * Define a polymorphic one-to-one relationship.
     */
    abstract public function morphOne( $related, $name, $type = null, $id = null, $localKey = null );

    /**
     * Define a one-to-one relationship.
     */
    abstract public function hasOne( $related, $foreignKey = null, $localKey = null );
}