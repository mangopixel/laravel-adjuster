<?php

namespace Mangopixel\Adjuster\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Mangopixel\Adjuster\Adjustment;

/**
 * This class is a collection of tests, testing that you can make extensions to the
 * package. These may include things like changing the default migrations, using
 * a custom Adjustment model or changing the name of the columns used.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
class ExtendAdjusterTest extends TestCase
{
    /**
     * You should be able to use your own model as the Adjustment model by changing
     * a value in the configurations and extending Mangopixel\Adjuster\Adjustment.
     *
     * @test
     */
    public function youCanExtendAdjustmentModel()
    {
        // Arrange...
        $model = new class extends Adjustment
        {
            protected $table = 'adjustments';
        };

        config( [
            'adjuster.adjustment_model' => get_class( $model )
        ] );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( get_class( $model ), get_class( $adjustment ) );
        $this->assertEquals( $adjustment->changes, [
            'price' => 20
        ] );

        $this->seeInDatabase( 'adjustments', [
            'changes' => json_encode( [
                'price' => 20
            ] )
        ] );
    }

    /**
     * You may also use a clean model without extending the base adjustment model. We need
     * to disable timestamps manually since we're not extending the base model.
     *
     * @test
     */
    public function youCanCreateACleanAdjustmentModel()
    {
        // Arrange...
        $model = new class extends Model
        {
            protected $casts = [ 'changes' => 'array' ];
            protected $table = 'adjustments';
            public $timestamps = false;
        };

        config( [
            'adjuster.adjustment_model' => get_class( $model )
        ] );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( get_class( $model ), get_class( $adjustment ) );
        $this->assertEquals( $adjustment->changes, [
            'price' => 20
        ] );

        $this->seeInDatabase( 'adjustments', [
            'changes' => json_encode( [
                'price' => 20
            ] )
        ] );
    }

    /**
     * By default the Adjustment model casts the changes JSON value to a PHP array.
     * However, you may instead choose to use a Laravel Collection.
     *
     * @test
     */
    public function youCanCastChangesToACollection()
    {
        // Arrange...
        $model = new class extends Model
        {
            protected $casts = [ 'changes' => 'collection' ];
            protected $table = 'adjustments';
            public $timestamps = false;
        };

        config( [
            'adjuster.adjustment_model' => get_class( $model )
        ] );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Assert...
        $this->assertEquals( $fruit->price, 20 );
        $this->assertEquals( $adjustment->changes, collect( [
            'price' => 20
        ] ) );

        $this->seeInDatabase( 'adjustments', [
            'changes' => json_encode( [
                'price' => 20
            ] )
        ] );
    }

    /**
     * You may also omit casting the changes attribute. It will then be a JSON string
     * and the package should figure out how to handle the persisting.
     *
     * @test
     */
    public function youCanOmitCastingChanges()
    {
        // Arrange...
        $model = new class extends Model
        {
            protected $table = 'adjustments';
            public $timestamps = false;
        };

        config( [
            'adjuster.adjustment_model' => get_class( $model )
        ] );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Assert...
        $this->assertEquals( $fruit->price, 20 );
        $this->assertEquals( $adjustment->changes, json_encode( [
            'price' => 20
        ] ) );

        $this->seeInDatabase( 'adjustments', [
            'changes' => json_encode( [
                'price' => 20
            ] )
        ] );
    }

    /**
     * If you like you should be able to enable the timestamps in the adjustments table.
     * This requires adding timestamp fields to the migrations.
     *
     * @test
     */
    public function youCanEnableTimestamps()
    {
        // Arrange...
        $model = new class extends Model
        {
            protected $table = 'adjustments';
        };

        config( [
            'adjuster.adjustment_model' => get_class( $model )
        ] );

        $this->schema->table( 'adjustments', function ( Blueprint $table ) {
            $table->timestamps();
        } );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $adjustment->changes, json_encode( [
            'price' => 20
        ] ) );

        $this->seeInDatabase( 'adjustments', [
            'changes' => json_encode( [
                'price' => 20
            ] )
        ] );
    }

    /**
     * You should be able to change the name of the foreign key column(s). When using
     * polymorphic relationships the value is split into two columns and suffixed
     * with id and type.
     *
     * @test
     */
    public function youCanChangeNameOfAdjustableColumn()
    {
        // Arrange...
        config( [
            'adjuster.adjustable_column' => 'changeable'
        ] );

        $this->schema->table( 'adjustments', function ( Blueprint $table ) {
            $table->renameColumn( 'adjustable_id', 'changeable_id' );
        } );
        $this->schema->table( 'adjustments', function ( Blueprint $table ) {
            $table->renameColumn( 'adjustable_type', 'changeable_type' );
        } );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $adjustment->adjustable, $fruit->fresh() );
        $this->assertEquals( $adjustment->changeable_id, $fruit->id );
        $this->assertEquals( $adjustment->changeable_type, $fruit->getMorphClass() );

        $this->seeInDatabase( 'adjustments', [
            'changeable_id' => $fruit->id,
            'changeable_type' => $fruit->getMorphClass(),
        ] );
    }

    /**
     * You should also be able to change the name of the changes JSON column.
     *
     * @test
     */
    public function youCanChangeNameOfChangesColumn()
    {
        // Arrange...
        $model = new class extends Adjustment
        {
            protected $casts = [ 'modifications' => 'array' ];
            protected $table = 'adjustments';
        };

        config( [
            'adjuster.adjustment_model' => get_class( $model ),
            'adjuster.changes_column' => 'modifications'
        ] );

        $this->schema->table( 'adjustments', function ( Blueprint $table ) {
            $table->renameColumn( 'changes', 'modifications' );
        } );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $adjustment->modifications, [
            'price' => 20
        ] );

        $this->seeInDatabase( 'adjustments', [
            'modifications' => json_encode( [
                'price' => 20
            ] )
        ] );
    }
}