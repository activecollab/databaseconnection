<?php

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateTimeValue;
use DateTime;
use DateTimeZone;

class EscapeTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp()
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnEmptyArray()
    {
        $this->connection->escapeValue([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnUsupportedObject()
    {
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
