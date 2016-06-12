<?php

namespace Mangopixel\Adjuster;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
        $this->publishes( [ __DIR__ . '/../resources/config/adjuster.php' => config_path( 'adjuster.php' ) ], 'config' );
        $this->publishes( [ __DIR__ . '/../resources/migrations' => database_path( 'migrations' ), ], 'migrations' );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom( __DIR__ . '/../resources/config/adjuster.php', 'adjuster' );
    }
}