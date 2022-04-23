<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateValue;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;

class EscapeTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new Connection($this->link);
    }

    /**
     * Test escape NULL.
     */
    public function testEscapeNull()
    {
        $this->assertEquals('NULL', $this->connection->escapeValue(null));
    }

    /**
     * Test escape boolean.
     */
    public function testEscapeBoolean()
    {
        $this->assertEquals("'1'", $this->connection->escapeValue(true));
        $this->assertEquals("'0'", $this->connection->escapeValue(false));
    }

    /**
     * Test escape string.
     */
    public function testEscapeString()
    {
        $this->assertEquals("'123'", $this->connection->escapeValue('123'));
    }

    /**
     * Test escape integer.
     */
    public function testEscapeInteger()
    {
        $this->assertEquals("'123'", $this->connection->escapeValue(123));
        $this->assertEquals("'-123'", $this->connection->escapeValue(-123));
    }

    /**
     * Test escape float.
     */
    public function testEscapeFloat()
    {
        $this->assertEquals("'123.456'", $this->connection->escapeValue(123.456));
        $this->assertEquals("'-123.456'", $this->connection->escapeValue(-123.456));
    }

    /**
     * Test escape DateValue instance.
     */
    public function testEscapeDateValue()
    {
        $this->assertEquals("'2015-08-11'", $this->connection->escapeValue(new DateValue('2015-08-11')));
    }

    /**
     * Test escape DateTimeValue instance.
     */
    public function testEscapeDateTimeValue()
    {
        $this->assertEquals("'2015-08-11 23:05:28'", $this->connection->escapeValue(new DateTimeValue('2015-08-11 23:05:28', 'UTC')));
    }

    /**
     * Test escape DateTime instance.
     */
    public function testEscapeDateTime()
    {
        $this->assertEquals("'2015-08-11 23:05:28'", $this->connection->escapeValue(new DateTime('2015-08-11 23:05:28', new DateTimeZone('UTC'))));
    }

    /**
     * Test escape array.
     */
    public function testEscapeArray()
    {
        $this->assertEquals("('1','2','3')", $this->connection->escapeValue([1, 2, 3]));
    }

    public function testExceptionOnEmptyArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->connection->escapeValue([]);
    }

    public function testExceptionOnUsupportedObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->connection->escapeValue($this->link);
    }

    /**
     * Test escape field name.
     */
    public function testEscapeFieldName()
    {
        $this->assertEquals('`id`', $this->connection->escapeFieldName('id'));
    }

    /**
     * Test escape table name.
     */
    public function testEscapeTableName()
    {
        $this->assertEquals('`users`', $this->connection->escapeFieldName('users'));
    }
}
