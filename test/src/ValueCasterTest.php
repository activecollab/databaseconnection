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
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DateValue\DateTimeValueInterface;
use ActiveCollab\DateValue\DateValueInterface;
use RuntimeException;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class ValueCasterTest extends TestCase
{
    /**
     * Test default casters.
     */
    public function testCasterStartsWithIdAndRowCountCasters()
    {
        $caster = new ValueCaster();

        $this->assertEquals(ValueCasterInterface::CAST_INT, $caster->getTypeByFieldName('id'));
        $this->assertEquals(ValueCasterInterface::CAST_INT, $caster->getTypeByFieldName('row_count'));
        $this->assertEquals(ValueCasterInterface::CAST_STRING, $caster->getTypeByFieldName('unknown_by_default'));
    }

    /**
     * Test if we can override 'id' and 'row_count' casters.
     */
    public function testIdAndRowCountCastersCanBeReconfigured()
    {
        $caster = new ValueCaster(['id' => ValueCasterInterface::CAST_BOOL, 'row_count' => ValueCasterInterface::CAST_DATETIME]);

        $this->assertEquals(ValueCasterInterface::CAST_BOOL, $caster->getTypeByFieldName('id'));
        $this->assertEquals(ValueCasterInterface::CAST_DATETIME, $caster->getTypeByFieldName('row_count'));
    }

    /**
     * Check if casting can be dictated per field.
     */
    public function testValueCastingCanBeDictated()
    {
        $default_caster = new ValueCaster();

        $this->assertEquals(ValueCasterInterface::CAST_STRING, $default_caster->getTypeByFieldName('budget'));

        $new_caster = new ValueCaster(['budget' => ValueCasterInterface::CAST_FLOAT]);

        $this->assertEquals(ValueCasterInterface::CAST_FLOAT, $new_caster->getTypeByFieldName('budget'));
    }

    /**
     * Test if type is properly detected by type.
     */
    public function testDetectTypeByFieldName()
    {
        $caster = new ValueCaster();

        $this->assertEquals(ValueCasterInterface::CAST_INT, $caster->getTypeByFieldName('project_leader_id'));

        $this->assertEquals(ValueCasterInterface::CAST_DATETIME, $caster->getTypeByFieldName('created_at'));
        $this->assertEquals(ValueCasterInterface::CAST_DATE, $caster->getTypeByFieldName('due_on'));
        $this->assertEquals(ValueCasterInterface::CAST_BOOL, $caster->getTypeByFieldName('is_important'));
        $this->assertEquals(ValueCasterInterface::CAST_BOOL, $caster->getTypeByFieldName('was_extracted'));
        $this->assertEquals(ValueCasterInterface::CAST_BOOL, $caster->getTypeByFieldName('had_trial'));
        $this->assertEquals(ValueCasterInterface::CAST_BOOL, $caster->getTypeByFieldName('were_imported'));
        $this->assertEquals(ValueCasterInterface::CAST_BOOL, $caster->getTypeByFieldName('have_been_reported'));
        $this->assertEquals(ValueCasterInterface::CAST_STRING, $caster->getTypeByFieldName('regular_field'));
    }

    /**
     * Test if NULL value is not cast to other native types.
     */
    public function testNullRemainsNull()
    {
        $caster = new ValueCaster();

        $this->assertEquals(ValueCasterInterface::CAST_INT, $caster->getTypeByFieldName('project_leader_id'));

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
            'budget' => ValueCasterInterface::CAST_FLOAT,
            'json_empty' => ValueCasterInterface::CAST_JSON,
            'json_object' => ValueCasterInterface::CAST_JSON,
            'json_array' => ValueCasterInterface::CAST_JSON,
            'json_scalar' => ValueCasterInterface::CAST_JSON,
        ]))->castRowValues($row);

        $this->assertIsInt($row['id']);
        $this->assertEquals(456, $row['id']);

        $this->assertIsInt($row['project_leader_id']);
        $this->assertEquals(123, $row['project_leader_id']);

        $this->assertIsString($row['name']);
        $this->assertEquals('Project name', $row['name']);

        $this->assertInstanceOf(DateTimeValueInterface::class, $row['created_at']);
        $this->assertInstanceOf(DateValueInterface::class, $row['updated_on']);

        $this->assertIsBool($row['is_important']);
        $this->assertTrue($row['is_important']);

        $this->assertArrayHasKey('completed_at', $row);
        $this->assertNull($row['completed_at']);

        $this->assertIsFloat($row['budget']);
        $this->assertEquals(1200.50, $row['budget']);

        $this->assertNull($row['json_empty']);

        $this->assertIsArray($row['json_object']);
        $this->assertEquals(['first' => 12, 'second' => '13'], $row['json_object']);

        $this->assertIsArray($row['json_array']);
        $this->assertEquals([1, 2, 3, 4, 5], $row['json_array']);

        $this->assertIsInt($row['json_scalar']);
        $this->assertEquals(12345, $row['json_scalar']);
    }

    public function testInvalidJsonBreaksCasting()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/Failed to parse JSON. Reason: (.*)\w+/");

        $row = ['broken_json' => '{"broken":"object'];

        (new ValueCaster(['broken_json' => ValueCasterInterface::CAST_JSON]))->castRowValues($row);
    }

    /**
     * Confirm that previous JSON decoding does not affect value decoding done by value caster.
     */
    public function testOldJsonErrorDoesNotAffectCasting()
    {
        json_decode('{"broken":"object');
        $this->assertNotEmpty(json_last_error());

        $row = ['ok_json' => '{"ok":"json"}'];

        (new ValueCaster(['ok_json' => ValueCasterInterface::CAST_JSON]))->castRowValues($row);
    }
}
