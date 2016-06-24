# Laravel Adjuster

[![Latest Stable Version](https://poser.pugx.org/mangopixel/laravel-adjuster/v/stable?format=flat-square)](https://github.com/mangopixel/laravel-adjuster)
[![Packagist Downloads](https://img.shields.io/packagist/dt/mangopixel/laravel-adjuster.svg?style=flat-square)](https://packagist.org/packages/mangopixel/laravel-adjuster)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](license.md)
[![Build Status](https://img.shields.io/travis/mangopixel/laravel-adjuster/master.svg?style=flat-square)](https://travis-ci.org/mangopixel/laravel-adjuster)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/mangopixel/laravel-adjuster.svg?style=flat-square)](https://scrutinizer-ci.com/g/mangopixel/laravel-adjuster/?branch=master)


A Laravel package for updating your Eloquent models indirectly using an adjustments table. This allows you to overwrite a model's attributes without changing the model directly. This can be useful in cases where you don't have control over the data flow of your models. 

A concrete example of its usefulness is when you feed a table with data from an API, and use a cron job to keep the table updated with the most recent data. In this case you might want to keep the table untouched so your changes are not overwritten by newer updates without you realising. Updating the table using an adjuster solves this problem as all adjustments you make to the data are stored in another table.

Most of the functionality lives in a trait, making it easy to use in your models. You can also adjust multiple models using the same adjustments table, as it uses polymorphic relationships. The package is well tested and extremely lightweight.

## Requirements

This package requires:
- PHP 7.0+
- Laravel 5.0+

The default adjustments migration also uses a JSON column to store changes. JSON columns are only supported in MySQL version 5.7 or higher and other databased that support Json. You may also change the migration data type to something else (like text) if your selected database doesn't support JSON columns.

## Installation

Install the package through Composer:

```shell
composer require mangopixel/laravel-adjuster
```

After updating Composer, append the following service provider to the `providers` key in `config/app.php`:

```php
Mangopixel\Adjuster\AdjusterServiceProvider::class
```

You may also publish the package configuration file and migrations using the following Artisan command:

```shell
php artisan vendor:publish --provider="Mangopixel\Adjuster\AdjusterServiceProvider"
```

The configuration file is well documented and you may edit it to suit your needs. You may also edit the migration file to your liking. However, make sure you update the `adjustable_column` and `changes_column` values in the configuration if you change the default column names.

## Usage

After installing and adding the service provider, you should migrate the `adjustments` table. If you have published the vendor files as described in the installation guide above you should have the new migration file in the migrations folder. In that case you can just run `php artisan migrate`.

### Adding the trait and contract

You need to use the `Mangopixel\Adjuster\CanBeAdjusted` trait for every model you want to be able to adjust together with the `Mangopixel\Adjuster\Adjustable` interface which the trait fulfills. An example model:

```php
<?php

namespace App;

use Mangopixel\Adjuster\Adjustable;
use Mangopixel\Adjuster\CanBeAdjusted;

class Fruit extends Model implements Adjustable
{
  use CanBeAdjusted;
}
```

### Making adjustments

The `Mangopixel\Adjuster\CanBeAdjusted` trait gives you access to multiple methods, among others the adjust method, which you can use to make a new adjustment to the model:

```php
$fruit->adjust( [
    'name' => 'Mango',
    'price' => 100
] );
```

The above will create a new row in the adjustments table linking to the Fruit model through a polymorphic relationship (with `adjustable_id` and `adjustable_type`). The row will also have a JSON column containing the updated name and price. If a row already exists, however, it will instead merge the new changes with the old ones.

If you have additional columns in the adjustments table you may pass these along as a second optional parameter:

```php
$fruit->adjust( [
    'name' => 'Mango',
    'price' => 100
], [
    'message' => 'This adjustment was fruitful'
] );
```

You can read more about adding columns to the adjustments table in the extensions section below.

### Removing adjustments

Once an adjustment has been set in the changes JSON, you may remove it again by passing in a null value when adjusting:

```php
$fruit->adjust( [
    'name' => null
] );
```

The above will remove the name adjustment from the JSON column in the `adjustments` table and revert the model's value to its original state. If you remove all changes in the JSON column, the row in the `adjustments` table will disappear entirely.

*The package will listen for updates on the adjustable model and if any of the attributes changes to the same value as the adjusted value the adjustment will automatically be removed.*

### Applying adjustments

If you retrieve the Fruit model from the example above, you will get the original values, and not the adjusted ones. This is because the package gives you the option of when you want to adjust the model, by providing an `applyAdjustments` method:

```php
$fruit->applyAdjustments();
```

This will fill the model's attributes with the values from the `adjustments` tables. It will not persist the new values to the model's table. If you try to save() a model after applying adjustments you will get a `\Mangopixel\Adjuster\ModelAdjustedException` exception. The idea of the package is to not update the model directly, it therefore protects you from ever persisting the changes applied from the adjustments to the database.

If you call `applyAdjustments` when there is no existing adjustment record, no adjustments will be applied, but you will not get an error. You may then use the `isAdjusted` method from the `Mangopixel\Adjuster\CanBeAdjusted` trait to check if there actually was any adjustments applied or not:

```php
$fruit->isAdjusted();
```

This function will check if the model has called `applyAdjustments` and has active adjustments. 

### Disable save protection

If you don't want the package to throw an exception when trying to save a model instance where changes from the `adjustments` table has been applied, you may disable the `save_protection` in the configuration file.

If you only want to disable it on individual models you can also add a new property to the model:

```php
protected $saveProtection = false;
```

You may also check if save protection is enabled on a current model using the `hasSaveProtection` method from the `Mangopixel\Adjuster\CanBeAdjusted` trait:

```php
$fruit->hasSaveProtection();
```

### Accessing the adjustment model

The package provides an `Adjustment` model for the `adjustments` table. You can access the model relationship using the `adjustment` function from the `HasAdjustment` trait:

```php
$model->adjustment;
```

Which returns the adjustment row relating to the model, if one exists. You also have the opposite relation specified in the `Adjustment` model:

```php
$adjustment->adjustable;
```

This will return the model relating to the adjustment. Do note that this is only true if you have polymorphic relationships enabled. If you disable polymorphic relations you will not have access to an inverse relationship, simply because the adjustment model cannot guess which model it's related to. If you still want the inverse relation you can extend the Adjustment model and add the relationship function in there.

### Disabling polymorphic relationships

If you're only planning on adjusting one kind of model, you might fine it a bit overkill to use polymorphic relationships. Don't worry, it's a breeze to disable it. Just set the `polymorphic` configuration key to false.

The `adjustable_column` key works slightly different depending on wether or not polymorphic relations are enabled or not. If they are enabled, two columns will be created in the adjustments table: one with ```_id``` suffix and one with ```_type``` suffix. However, with polymorphic relations disabled it will only create one foreign key column.

So, to give an example. Let's say you have an adjustable Fruit model, and this is the only model you want to adjust. You may disable polymorphic relations in the configuration and set the `adjustable_column` to `fruit_id`. And you're done, you now have a one to one relationship between an adjustment and a fruit.

## Extension

### Extending the Adjustment model

If you would like to extend the functionality of the `Adjustment` model you may simply create a new model that extends `Mangopixel\Adjuster\Adjustment`. You will also need to update the `adjustment_model` in the configuration file so the package knows which class to use as the model.

You may also just create a new model from scratch, just make sure to disable timestamps (if you don't add the timestamps columns to the migration file). The only thing nessecary for the model to work out of the box:

```php
<?php

namespace App;

class Adjustment extends Model
{
    public $timestamps = false;
}
```

You may also add a `protected $casts` to always get an array when you try to access `$adjustment->changes`:

```php
protected $casts = [ 'changes' => 'array' ]
```

You may also cast it to a Laravel Collection using `'collection'` as casting value. Laravel Adjuster will do the correct casting for you when persisting adjustments to the database.

### Modifying the migration file

You may add new columns to the adjustments table migration file. If for some reason the default column names don't fit your needs, feel free to change them to whatever suits your needs. Just make sure to update the `adjustable_column` and `changes_column` in the configuration file accordingly.

The migration uses a json field by default. This is only supported in MySql 5.7+, you should however be able to change the field to a text field instead without any repercussions.

You may also change the table name, but you will need to create your own adjustment model and set the `protected $table` to whatever you like.

## License

Laravel Adjuster is free software distributed under the terms of the MIT license.
