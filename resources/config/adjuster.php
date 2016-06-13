<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Adjustment Model Path
    |--------------------------------------------------------------------------
    |
    | This key identify which model should be used for creating adjustments.
    | If you're not satisfied with the model provided by the package, you
    | may create a new model and set the class path below accordingly.
    |
    */

    'adjustment_model' => \Mangopixel\Adjuster\Adjustment::class,

    /*
    |--------------------------------------------------------------------------
    | Adjustable Column Name
    |--------------------------------------------------------------------------
    |
    | If you change the model you may also like to customize the name of the
    | table columns. This key is the name of the polymorphic relationship
    | between an adjustment and an adjustable model. Change it as fit.
    |
    */

    'adjustable_column' => 'adjustable',

    /*
    |--------------------------------------------------------------------------
    | Changes Column Name
    |--------------------------------------------------------------------------
    |
    | All changes in an adjustment is saved in a JSON column. This column is
    | by default named changes, but you may change the value below if you
    | want a different column name. Make sure to change the fillables.
    |
    */

    'changes_column' => 'changes',

    /*
    |--------------------------------------------------------------------------
    | Save Protection
    |--------------------------------------------------------------------------
    |
    | Because the package is supposed to make adjustments to a model without
    | changing the model directly, it will protect you from persisting an
    | adjusted model, feel free to turn off the save protection below.
    |
    */

    'save_protection' => true

];