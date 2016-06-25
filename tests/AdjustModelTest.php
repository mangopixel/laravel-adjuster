<?php

namespace Mangopixel\Adjuster\Tests;

use Illuminate\Database\Schema\Blueprint;
use Mangopixel\Adjuster\Exceptions\ModelAdjustedException;

/**
 * This class is a collection of tests, testing that you can successfully adjust models
 * and that the package behaves as it's supposed to.
 *
 * @package Laravel Adjuster
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
class AdjustModelTest extends TestCase
{
    /**
     * You can use the adjust method from the HasAdjustments trait to indirectly adjust
     * the model by creating a new row in the adjustments table.
     *
     * @test
     */
    public function youCanAdjustModels()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        $this->assertFalse( $fruit->isAdjusted() );
        $this->assertEquals( $adjustment->adjustable_id, $fruit->id );
        $this->assertEquals( $adjustment->adjustable_type, $fruit->getMorphClass() );
        $this->assertEquals( $adjustment->changes, [
            'price' => 20
        ] );

        $this->seeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes' => json_encode( [
                'price' => 20
            ] )
        ] );
    }

    /**
     * When using the adjust method on a model it should only create a new adjustment
     * record and not make any changes to the model itself.
     *
     * @test
     */
    public function itOnlyCreatesAdjustmentIfNewValues()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        // Act...
        $fruit->adjust( [
            'price' => 10
        ] );

        // Assert...
        $this->dontSeeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id
        ] );
    }

    /**
     * When adjusting a model multiple times, only one adjustment record should ever be
     * stored per model. Any following adjustments will be merged into the first.
     *
     * @test
     */
    public function itShouldMergeAllChangesIntoOneAdjustment()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        // Act...
        $fruit->adjust( [
            'name' => 'Kiwi'
        ] );

        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $adjustment->changes, [
            'name' => 'Kiwi',
            'price' => 20
        ] );

        $this->seeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes' => json_encode( [
                'name' => 'Kiwi',
                'price' => 20
            ] )
        ] );
    }

    /**
     * When using the adjust method on a model it should only create a new adjustment
     * record and not make any changes to the model itself.
     *
     * @test
     */
    public function itShouldNotChangeTheModelDirectly()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        // Act...
        $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $fruit->name, 'Mango' );
        $this->assertEquals( $fruit->price, 10 );

        $this->seeInDatabase( 'fruits', [
            'name' => 'Mango',
            'price' => 10
        ] );
    }

    /**
     * Once you've adjusted a model and made changes to its values through the adjustments
     * table, you can remove these changes by adjusting the model again and setting the
     * changes you want to remove to null.
     *
     * @test
     */
    public function youCanUnsetChanges()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        $fruit->adjust( [
            'name' => 'Kiwi',
            'price' => 20
        ] );

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => null
        ] );

        // Assert...
        $this->assertEquals( $adjustment->changes, [
            'name' => 'Kiwi'
        ] );

        $this->seeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes' => json_encode( [
                'name' => 'Kiwi'
            ] )
        ] );
    }

    /**
     * Once you've adjusted a model and made changes to its values through the adjustments
     * table, you can remove these changes by adjusting the model again and setting the
     * changes you want to remove to null.
     *
     * @test
     */
    public function itShouldUnsetChangeIfSameAsOriginal()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        $fruit->adjust( [
            'name' => 'Kiwi',
            'price' => 20
        ] );

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 10
        ] );

        // Assert...
        $this->assertEquals( $adjustment->changes, [
            'name' => 'Kiwi'
        ] );

        $this->seeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes' => json_encode( [
                'name' => 'Kiwi'
            ] )
        ] );
    }

    /**
     * If you unset all changes in an adjustments, the entire record should be removed.
     *
     * @test
     */
    public function itShouldRemoveTheAdjustmentIfNoChangesAreSet()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        $fruit->adjust( [
            'price' => 20
        ] );

        // Act...
        $fruit->adjust( [
            'price' => null
        ] );

        // Assert...
        $this->dontSeeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id
        ] );
    }

    /**
     * If you try to adjust a model using attributes that don't exist in the given model,
     * it wont actually save the adjustments.
     *
     * @test
     */
    public function itShouldNotAddAdjustmentsWithInvalidData()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        // Act...
        $fruit->adjust( [
            'invalid' => 123
        ] );

        // Assert...
        $this->dontSeeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id
        ] );
    }

    /**
     * You may use the applyAdjustments method on the HasAdjustments trait to apply any
     * adjustments set to the model. This will not persist the adjustments, but just
     * fill the model instance with the adjustments data.
     *
     * @test
     */
    public function youCanApplyAdjustmentsToModel()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        $fruit->adjust( [
            'price' => 20
        ] );

        // Act...
        $fruit->applyAdjustments();

        // Assert...
        $this->assertTrue( $fruit->isAdjusted() );
        $this->assertEquals( $fruit->name, 'Mango' );
        $this->assertEquals( $fruit->price, 20 );

        $this->seeInDatabase( 'fruits', [
            'name' => 'Mango',
            'price' => 10
        ] );
    }

    /**
     * If there are no adjustments stored for the given model you're trying to call
     * applyAdjustments on, there should be no errors thrown and the model's data
     * should not be modified.
     *
     * @test
     */
    public function youCanCallApplyAdjustmentsWhenNoAdjustmentsExist()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        // Act...
        $fruit->applyAdjustments();

        // Assert...
        $this->assertFalse( $fruit->isAdjusted() );
        $this->assertEquals( $fruit->name, 'Mango' );
        $this->assertEquals( $fruit->price, 10 );

        $this->seeInDatabase( 'fruits', [
            'name' => 'Mango',
            'price' => 10
        ] );
    }

    /**
     * If the save protection configuration is not disabled it should throw an exception
     * if you try to persist the changes to the model after applying adjustments.
     *
     * @test
     */
    public function itShouldProtectYouFromSavingAdjustedModels()
    {
        // Arrange...
        $this->expectException( ModelAdjustedException::class );

        $fruit = $this->createTestModel();

        $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Act...
        $fruit->save();

        // Assert...
        $this->assertTrue( $fruit->hasSaveProtection() );

        $this->seeInDatabase( 'fruits', [
            'name' => 'Mango',
            'price' => 10
        ] );
    }

    /**
     * If the save protection configuration is not disabled it should throw an exception
     * if you try to persist the changes to the model after applying adjustments.
     *
     * @test
     */
    public function youCanDisableSaveProtection()
    {
        // Arrange...
        config( [
            'adjuster.save_protection' => false
        ] );

        $fruit = $this->createTestModel();

        $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Act...
        $fruit->save();

        // Assert...
        $this->assertFalse( $fruit->hasSaveProtection() );

        $this->seeInDatabase( 'fruits', [
            'name' => 'Mango',
            'price' => 20
        ] );
    }

    /**
     * You should be able to disable the save protection on individual models.
     *
     * @test
     */
    public function youCanDisableSaveProtectionPerModel()
    {
        // Arrange...
        $fruit = $this->createUnprotectedTestModel();

        $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Act...
        $fruit->save();

        // Assert...
        $this->assertFalse( $fruit->hasSaveProtection() );

        $this->seeInDatabase( 'fruits', [
            'name' => 'Mango',
            'price' => 20
        ] );
    }

    /**
     * You should also be able to disable save protection globally and then enable it
     * on individual models.
     *
     * @test
     */
    public function youCanEnableSaveProtectionPerModel()
    {
        // Arrange...
        $this->expectException( ModelAdjustedException::class );

        config( [
            'adjuster.save_protection' => false
        ] );

        $fruit = $this->createProtectedTestModel();

        $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Act...
        $fruit->save();

        // Assert...
        $this->assertTrue( $fruit->hasSaveProtection() );

        $this->seeInDatabase( 'fruits', [
            'name' => 'Mango',
            'price' => 10
        ] );
    }

    /**
     * The HasAdjustments trait provides an adjustment relation method for all adjustable
     * models. Likewise you have an adjustable relation method on the Adjustment model
     * provided by the package.
     *
     * @test
     */
    public function itShouldCreateRelations()
    {
        // Arrange...
        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $fruit->adjustment, $adjustment->fresh() );
        $this->assertEquals( $adjustment->adjustable, $fruit->fresh() );
    }

    /**
     * If you only want to adjust one model you might not need polymorphic relations,
     * so you should be able to disable it and use a single foreign key instead.
     * The adjustable relationship must be made manually if not polymorphic.
     *
     * @test
     */
    public function youCanDisablePolymorphicRelations()
    {
        // Arrange...
        config( [
            'adjuster.polymorphic' => false,
            'adjuster.adjustable_column' => 'fruit_id'
        ] );

        $this->schema->table( 'adjustments', function ( Blueprint $table ) {
            $table->dropColumn( [ 'adjustable_id', 'adjustable_type' ] );
        } );

        $this->schema->table( 'adjustments', function ( Blueprint $table ) {
            $table->unsignedInteger( 'fruit_id' )->nullable();
        } );

        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $fruit->adjustment, $adjustment->fresh() );
    }

    /**
     * You may also pass along additional attribute value pairs as the second argument
     * of the adjust method.
     *
     * @test
     */
    public function youCanAddColumnsToAdjustmentsTable()
    {
        // Arrange...
        $this->schema->table( 'adjustments', function ( Blueprint $table ) {
            $table->addColumn( 'string', 'comment' )->nullable();
        } );

        $comment = 'Double price for mangos due to tax';
        $fruit = $this->createTestModel();

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ], [
            'comment' => $comment
        ] );

        $this->assertEquals( $adjustment->comment, $comment );
        $this->assertEquals( $adjustment->changes, [
            'price' => 20
        ] );

        $this->seeInDatabase( 'adjustments', [
            'comment' => $comment,
            'changes' => json_encode( [
                'price' => 20
            ] )
        ] );
    }
}