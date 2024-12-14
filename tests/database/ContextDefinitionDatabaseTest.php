<?php

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\Seeds\ContextDefinitionSeeder;
use Tests\Support\Models\ContextDefinitionModel;

/**
 * @internal
 */
final class ContextDefinitionDatabaseTest extends CIUnitTestCase
{

    protected $seed = ContextDefinitionSeeder::class;
    // protected $seedOnce = false;

    public function testModelFindAll(): void
    {
        $model = new ContextDefinitionModel();

        // Get every row created by ExampleSeeder
        $objects = $model->findAll();

        // Make sure the count is as expected
        $this->assertCount(6, $objects);
    }

    public function testSoftDeleteLeavesRow(): void
    {
        $model = new ContextDefinitionModel();
        $this->setPrivateProperty($model, 'useSoftDeletes', true);
        // $this->setPrivateProperty($model, 'tempUseSoftDeletes', true);

        /** @var stdClass $object */
        $object = $model->first();
        $model->delete($object->context_definition_id);

        // The model should no longer find it
        $this->assertNull($model->find($object->context_definition_id));

        // ... but it should still be in the database
        $result = $model->builder()->where('context_definition_id', $object->context_definition_id)->get()->getResult();

        $this->assertCount(1, $result);
    }
}
