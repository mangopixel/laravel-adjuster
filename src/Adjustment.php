<?php

namespace Mangopixel\Adjuster;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An Eloquent model used to represent an adjustment made to another model. If you want to
 * make any modifications to the model, you can extend it or create your own model from
 * scratch. Just make sure to update the adjustable_model key in the configurations.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
class Adjustment extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'changes' => 'array'
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'adjustable_id',
        'adjustable_type'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the adjustable model associated with the adjustment.
     *
     * @return MorphTo
     */
    public function adjustable():MorphTo
    {
        return $this->morphTo( config( 'adjuster.adjustable_column' ) );
    }
}