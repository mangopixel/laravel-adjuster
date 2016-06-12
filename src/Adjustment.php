<?php

namespace Mangopixel\Adjuster;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'adjustable_id',
        'adjustable_type',
        'changes'
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adjustable()
    {
        return $this->morphTo();
    }

    /**
     * Get the adjustable model associated with the adjustment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getChangesAttribute( $changes )
    {
        return json_decode( $changes, true );
    }
}