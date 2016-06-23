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
    | Save Protection
    |--------------------------------------------------------------------------
    |
    | Because the package is supposed to make adjustments to a model without
    | changing the model directly, it will protect you from persisting an
    | adjusted model, feel free to turn off the save protection below.
    |
    */

    'save_protection' => true,

    /*
    |--------------------------------------------------------------------------
    | Polymorphic Relationships
    |--------------------------------------------------------------------------
    |
    | By default there will be a polymorphic relationship between adjustment
    | model and the models with the CanBeAdjusted trait. If you only have
    | one model getting adjusted you can disable polmorphic relations.
    |
    */
    'polymorphic' => true,

    /*
    |--------------------------------------------------------------------------
    | Adjustable Column Name
    |--------------------------------------------------------------------------
    |
    | If you change the Adjustment model, you can also customize the name of
    | the columns. With polymorphic relations it creates two columns with
    | id and type suffixes, else it creates one column with no suffix.
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
    | want a different column name. Make sure to update the fillables.
    |
    */

    'changes_column' => 'changes'

];