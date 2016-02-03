<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DateValue\DateTimeValueInterface;
use ActiveCollab\DateValue\DateValueInterface;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
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
        $row = [
            'id' => '456',
            'project_leader_id' => '123',
            'name' => 'Project name',
            'created_at' => '2015-08-12 20:00:15',
            'updated_on' => '2015-10-10',
            'is_important' => '1',
            'completed_at' => null,
            'budget' => '1200.50',
            'json_empty' => '',
            'json_object' => '{"first": 12,"second": "13"}',
            'json_array' => '[1,2,3,4,5]',
            'json_scalar' => '12345',
        ];

        (new ValueCaster([
            'budget' => ValueCaster::CAST_FLOAT,
            'json_empty' => ValueCaster::CAST_JSON,
            'json_object' => ValueCaster::CAST_JSON,
            'json_array' => ValueCaster::CAST_JSON,
            'json_scalar' => ValueCaster::CAST_JSON,
        ]))->castRowValues($row);

        $this->assertInternalType('integer', $row['id']);
        $this->assertEquals(456, $row['id']);

        $this->assertInternalType('integer', $row['project_leader_id']);
        $this->assertEquals(123, $row['project_leader_id']);

        $this->assertInternalType('string', $row['name']);
        $this->assertEquals('Project name', $row['name']);

        $this->assertInstanceOf(DateTimeValueInterface::class, $row['created_at']);
        $this->assertInstanceOf(DateValueInterface::class, $row['updated_on']);

        $this->assertInternalType('boolean', $row['is_important']);
        $this->assertTrue($row['is_important']);

        $this->assertArrayHasKey('completed_at', $row);
        $this->assertNull($row['completed_at']);

        $this->assertInternalType('float', $row['budget']);
        $this->assertEquals(1200.50, $row['budget']);

        $this->assertNull($row['json_empty']);

        $this->assertInternalType('array', $row['json_object']);
        $this->assertEquals(['first' => 12, 'second' => '13'], $row['json_object']);

        $this->assertInternalType('array', $row['json_array']);
        $this->assertEquals([1, 2, 3, 4, 5], $row['json_array']);

        $this->assertInternalType('integer', $row['json_scalar']);
        $this->assertEquals(12345, $row['json_scalar']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to parse JSON. Reason: Control character error, possibly incorrectly encoded
     */
    public function testInvalidJsonBreaksCasting()
    {
        $row = ['broken_json' => '{"broken":"object'];

        (new ValueCaster(['broken_json' => ValueCaster::CAST_JSON]))->castRowValues($row);
    }

    /**
     * Confirm that previous JSON decoding does not affect value decoding done by value caster.
     */
    public function testOldJsonErrorDoesNotAffectCasting()
    {
        json_decode('{"broken":"object');
        $this->assertNotEmpty(json_last_error());

        $row = ['ok_json' => '{"ok":"json"}'];

        (new ValueCaster(['ok_json' => ValueCaster::CAST_JSON]))->castRowValues($row);
    }
}
