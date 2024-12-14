<?php

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\Seeds\UserSeeder;
use Tests\Support\Models\UserModel;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UserDatabaseTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    protected $seed = UserSeeder::class;
    protected $seedOnce = true;
    protected $refresh = false;

    public function testModelFindAll(): void
    {
        $model = new UserModel();

        // Get every row created by ExampleSeeder
        $objects = $model->findAll();

        // print_r($objects);

        // Make sure the count is as expected
        $this->assertCount(3, $objects);
        $this->assertEquals('joedoe', $objects[0]->user_name);
    }

    public function testSoftDeleteLeavesRow(): void
    {
        $model = new UserModel();
        $this->setPrivateProperty($model, 'useSoftDeletes', true);
        // $this->setPrivateProperty($model, 'tempUseSoftDeletes', true);

        /** @var stdClass $object */
        $object = $model->first();
        $model->delete($object->user_id);

        // The model should no longer find it
        $this->assertNull($model->find($object->user_id));

        // ... but it should still be in the database
        $result = $model->builder()->where('user_id', $object->user_id)->get()->getResult();

        $this->assertCount(1, $result);
    }
}
