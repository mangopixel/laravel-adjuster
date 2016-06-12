<?php

namespace Mangopixel\Adjuster\Tests;

use Mangopixel\Adjuster\ModelAdjustedException;

class AdjusterTest extends TestCase
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
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

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
            'adjustable_id'   => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes'         => json_encode( [
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
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

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
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        // Act...
        $fruit->adjust( [
            'name' => 'Kiwi'
        ] );

        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $adjustment->changes, [
            'name'  => 'Kiwi',
            'price' => 20
        ] );

        $this->seeInDatabase( 'adjustments', [
            'adjustable_id'   => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes'         => json_encode( [
                'name'  => 'Kiwi',
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
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        // Act...
        $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $fruit->name, 'Mango' );
        $this->assertEquals( $fruit->price, 10 );

        $this->seeInDatabase( 'fruits', [
            'name'  => 'Mango',
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
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        $fruit->adjust( [
            'name'  => 'Kiwi',
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
            'adjustable_id'   => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes'         => json_encode( [
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
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        $fruit->adjust( [
            'name'  => 'Kiwi',
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
            'adjustable_id'   => $fruit->id,
            'adjustable_type' => $fruit->getMorphClass(),
            'changes'         => json_encode( [
                'name' => 'Kiwi'
            ] )
        ] );
    }

    /**
     * If you unset all changes in an adjustments, the entire record should be removed.
     *
     * @test
     */
    public function itShouldRemoveTheAdjustmentWhenNoChanges()
    {
        // Arrange...
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        $fruit->adjust( [
            'name'  => 'Kiwi',
            'price' => 20
        ] );

        // Act...
        $fruit->adjust( [
            'name'  => 'Mango',
            'price' => null
        ] );

        // Assert...
        $this->dontSeeInDatabase( 'adjustments', [
            'adjustable_id' => $fruit->id
        ] );
    }

    /**
     * If you unset all changes in an adjustments, the entire record should be removed.
     *
     * @test
     */
    public function itRemovesTheAdjustmentIfNoChangesAreSet()
    {
        // Arrange...
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

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
     * You may use the applyAdjustments method on the HasAdjustments trait to apply any
     * adjustments set to the model. This will not persist the adjustments, but just
     * fill the model instance with the adjustments data.
     *
     * @test
     */
    public function youCanApplyAdjustmentsToModel()
    {
        // Arrange...
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

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
            'name'  => 'Mango',
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

        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Act...
        $fruit->save();

        // Assert...
        $this->assertTrue( $fruit->hasSaveProtection() );
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

        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        $fruit->adjust( [
            'price' => 20
        ] );

        $fruit->applyAdjustments();

        // Act...
        $fruit->save();

        // Assert...
        $this->assertFalse( $fruit->hasSaveProtection() );

        $this->seeInDatabase( 'fruits', [
            'name'  => 'Mango',
            'price' => 20
        ] );
    }

    /**
     * The HasAdjustments trait provides an adjustment relation method for all adjustable
     * models. Likewise you have an adjustable relation method on the Adjustment model
     * provided by the package
     *
     * @test
     */
    public function itShouldCreateRelations()
    {
        // Arrange...
        $fruit = $this->createTestModel( [
            'name'  => 'Mango',
            'price' => 10
        ] );

        // Act...
        $adjustment = $fruit->adjust( [
            'price' => 20
        ] );

        // Assert...
        $this->assertEquals( $fruit->adjustment, $adjustment->fresh() );
        $this->assertEquals( $adjustment->adjustable, $fruit->fresh() );
    }
}