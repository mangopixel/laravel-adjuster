<?php

namespace Mangopixel\Adjuster;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * The Laravel Adjuster service provider, which is where the package is bootstrapped.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
class AdjusterServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes( [
            __DIR__ . '/../resources/config/adjuster.php' => config_path( 'adjuster.php' )
        ], 'config' );

        $timestamp = date( 'Y_m_d_His', time() );
        $this->publishes( [
            __DIR__ . '/../resources/migrations/create_adjustments_table.php.stub' => database_path( 'migrations' ) . '/' . $timestamp . '_create_adjustments_table.php',
        ], 'migrations' );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom( __DIR__ . '/../resources/config/adjuster.php', 'adjuster' );

        $this->app->bind( 'adjuster.model', function ( $app ) {
            return new $app->config[ 'adjuster.adjustment_model' ];
        } );
    }
}