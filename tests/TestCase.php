<?php

namespace Mangopixel\Adjuster\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Mangopixel\Adjuster\AdjusterServiceProvider;
use Mangopixel\Adjuster\CanBeAdjusted;
use Mangopixel\Adjuster\Contracts\Adjustable;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * This is the base test case class and is where the testing environment bootstrapping
 * takes place. All other testing classes should extend from this class.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Save an instance of the schema builder for easy access.
     *
     * @var Builder
     */
    protected $schema;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->schema = $this->app[ 'db' ]->connection()->getSchemaBuilder();
        $this->runTestMigrations();

        $this->beforeApplicationDestroyed( function () {
            $this->rollbackTestMigrations();
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
            'driver' => 'sqlite',
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
        include_once __DIR__ . '/../resources/migrations/create_adjustments_table.php.stub';
        ( new \CreateAdjustmentsTable() )->up();

        if ( ! $this->schema->hasTable( 'fruits' ) ) {
            $this->schema->create( 'fruits', function ( Blueprint $table ) {
                $table->increments( 'id' );
                $table->string( 'name' );
                $table->integer( 'price' );
            } );
        }
    }

    /**
     * Rollback migrations for tables only used for testing purposes.
     *
     * @return void
     */
    protected function rollbackTestMigrations()
    {
        ( new \CreateAdjustmentsTable() )->down();

        $this->schema->drop( 'fruits' );
    }

    /**
     * Creates a new adjustable model for testing purposes.
     *
     * @param  array $attributes
     * @return Adjustable
     */
    protected function createTestModel( array $attributes = [ ] ):Adjustable
    {
        $model = new class extends Model implements Adjustable
        {
            use CanBeAdjusted;

            protected $fillable = [ 'name', 'price' ];
            protected $table = 'fruits';
            public $timestamps = false;

            public function getMorphClass()
            {
                return $this->getTable();
            }
        };

        return $this->storeAdjustableModel( $model, $attributes );
    }

    /**
     * Creates a new adjustable model for testing purposes with save protection disabled.
     *
     * @param  array $attributes
     * @return Adjustable
     */
    protected function createUnprotectedTestModel( array $attributes = [ ] ):Adjustable
    {
        $model = new class extends Model implements Adjustable
        {
            use CanBeAdjusted;

            protected $saveProtection = false;
            protected $fillable = [ 'name', 'price' ];
            protected $table = 'fruits';
            public $timestamps = false;

            public function getMorphClass()
            {
                return $this->getTable();
            }
        };

        return $this->storeAdjustableModel( $model, $attributes );
    }

    /**
     * Creates a new adjustable model for testing purposes with save protection enabled.
     *
     * @param  array $attributes
     * @return Adjustable
     */
    protected function createProtectedTestModel( array $attributes = [ ] ):Adjustable
    {
        $model = new class extends Model implements Adjustable
        {
            use CanBeAdjusted;

            protected $saveProtection = true;
            protected $fillable = [ 'name', 'price' ];
            protected $table = 'fruits';
            public $timestamps = false;

            public function getMorphClass()
            {
                return $this->getTable();
            }
        };

        return $this->storeAdjustableModel( $model, $attributes );
    }

    /**
     * Stores an actual instance of an adjustable model to the database.
     *
     * @param  Model $model
     * @param  array $attributes
     * @return Adjustable
     */
    protected function storeAdjustableModel( Model $model, array $attributes = [ ] ):Adjustable
    {
        Relation::morphMap( [
            $model->getTable() => get_class( $model )
        ] );

        return $model->create( array_merge( [
            'name' => 'Mango',
            'price' => 10
        ], $attributes ) );
    }
}