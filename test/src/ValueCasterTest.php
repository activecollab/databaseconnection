<?php

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Record\ValueCaster;

class ValueCasterTest extends TestCase
{
    /**
     * Test defualt casters.
     */
    public function testCasterStartsWithIdAndRowCountCasters()
    {
        $caster = new ValueCaster();

        $this->assertEquals(ValueCaster::CAST_INT, $caster->getTypeByFieldName('id'));
        $this->assertEquals(ValueCaster::CAST_INT, $caster->getTypeByFieldName('row_count'));
        $this->assertEquals(ValueCaster::CAST_STRING, $caster->getTypeByFieldName('unknown_by_default'));
    }

    /**
     * Test if we can override 'id' and 'row_count' casters.
     */
    public function testIdAndRowCountCastersCanBeReconfigured()
    {
        $caster = new ValueCaster(['id' => ValueCaster::CAST_BOOL, 'row_count' => ValueCaster::CAST_DATETIME]);

        $this->assertEquals(ValueCaster::CAST_BOOL, $caster->getTypeByFieldName('id'));
        $this->assertEquals(ValueCaster::CAST_DATETIME, $caster->getTypeByFieldName('row_count'));
    }

    /**
     * Check if casting can be dictated per field.
     */
    public function testValueCastingCanBeDictated()
    {
        $default_caster = new ValueCaster();

        $this->assertEquals(ValueCaster::CAST_STRING, $default_caster->getTypeByFieldName('budget'));

        $new_caster = new ValueCaster(['budget' => ValueCaster::CAST_FLOAT]);

        $this->assertEquals(ValueCaster::CAST_FLOAT, $new_caster->getTypeByFieldName('budget'));
    }

    /**
     * Test if type is properly detected by type.
     */
    public function testDetectTypeByFieldName()
    {
        $caster = new ValueCaster();

        $this->assertEquals(ValueCaster::CAST_INT, $caster->getTypeByFieldName('project_leader_id'));

        $this->assertEquals(ValueCaster::CAST_DATETIME, $caster->getTypeByFieldName('created_at'));
        $this->assertEquals(ValueCaster::CAST_DATE, $caster->getTypeByFieldName('due_on'));
        $this->assertEquals(ValueCaster::CAST_BOOL, $caster->getTypeByFieldName('is_important'));
        $this->assertEquals(ValueCaster::CAST_STRING, $caster->getTypeByFieldName('regular_field'));
    }

    /**
     * Test if NULL value is not cast to other native types.
     */
    public function testNullRemainsNull()
    {
        $caster = new ValueCaster();

        $this->assertEquals(ValueCaster::CAST_INT, $caster->getTypeByFieldName('project_leader_id'));

        $row = ['project_leader_id' => null];

        $caster->castRowValues($row);

        $this->assertArrayHasKey('project_leader_id', $row);
        $this->assertNull($row['project_leader_id']);
    }

    /**
     * Test casting of all values in the row.
     */
    public function testRowValuesCasting()
    {
        $row = ['id' => '456', 'project_leader_id' => '123', 'name' => 'Project name', 'created_at' => '2015-08-12 20:00:15', 'is_important' => '1', 'completed_at' => null, 'budget' => '1200.50'];

        (new ValueCaster(['budget' => ValueCaster::CAST_FLOAT]))->castRowValues($row);

        $this->assertInternalType('integer', $row['id']);
        $this->assertEquals(456, $row['id']);

        $this->assertInternalType('integer', $row['project_leader_id']);
        $this->assertEquals(123, $row['project_leader_id']);

        $this->assertInternalType('string', $row['name']);
        $this->assertEquals('Project name', $row['name']);

        $this->assertInstanceOf('\DateTime', $row['created_at']);

        $this->assertInternalType('boolean', $row['is_important']);
        $this->assertTrue($row['is_important']);

        $this->assertArrayHasKey('completed_at', $row);
        $this->assertNull($row['completed_at']);

        $this->assertInternalType('float', $row['budget']);
        $this->assertEquals(1200.50, $row['budget']);
    }
}
