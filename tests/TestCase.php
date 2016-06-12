<?php

namespace Mangopixel\Adjuster\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mangopixel\Adjuster\Adjustable;
use Mangopixel\Adjuster\AdjusterServiceProvider;
use Mangopixel\Adjuster\HasAdjustments;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->runTestMigrations();
        $this->artisan( 'migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath( __DIR__ . '/../resources/migrations' ),
        ] );

        $this->beforeApplicationDestroyed( function () {
            $this->rollbackTestMigrations();
            $this->artisan( 'migrate:rollback' );
        } );
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp( $app )
    {
        $app[ 'config' ]->set( 'database.default', 'testbench' );
        $app[ 'config' ]->set( 'database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:'
        ] );
    }

    /**
     * Get package service providers.
     *
     * @return array
     */
    protected function getPackageProviders( $app )
    {
        return [
            AdjusterServiceProvider::class
        ];
    }

    /**
     * Run migrations for tables only used for testing purposes.
     *
     * @return void
     */
    protected function runTestMigrations()
    {
        if ( Schema::hasTable( 'fruits' ) ) {
            return;
        }

        Schema::create( 'fruits', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->integer( 'price' );
        } );
    }

    /**
     * Rollback migrations for tables only used for testing purposes.
     *
     * @return void
     */
    protected function rollbackTestMigrations()
    {
        Schema::drop( 'fruits' );
    }

    /**
     * Creates a new adjustable model for testing purposes.
     *
     * @param  array $attributes
     * @return void
     */
    protected function createTestModel( $attributes )
    {
        $model = new class extends Model implements Adjustable
        {
            use HasAdjustments;

            protected $fillable = [ 'name', 'price' ];
            protected $table = 'fruits';
            public $timestamps = false;

            public function getMorphClass()
            {
                return $this->getTable();
            }
        };

        Relation::morphMap( [
            $model->getTable() => get_class( $model )
        ] );

        return $model->create( $attributes );
    }
}