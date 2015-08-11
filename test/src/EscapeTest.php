<?php

  namespace ActiveCollab\DatabaseConnection\Test;

  use ActiveCollab\DatabaseConnection\Connection;
  use DateTime;
  use DateTimeZone;

  /**
   * @package ActiveCollab\DatabaseConnection\Test
   */
  class EscapeTest extends TestCase
  {
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up test environment
     */
    public function setUp()
    {
      parent::setUp();

      $this->connection = new Connection($this->link);
    }

    /**
     * Test escape NULL
     */
    public function testEscapeNull()
    {
      $this->assertEquals('NULL', $this->connection->escape(null));
    }

    /**
     * Test escape boolean
     */
    public function testEscapeBoolean()
    {
      $this->assertEquals("'1'", $this->connection->escape(true));
      $this->assertEquals("'0'", $this->connection->escape(false));
    }

    /**
     * Test escape string
     */
    public function testEscapeString()
    {
      $this->assertEquals("'123'", $this->connection->escape('123'));
    }

    /**
     * Test escape integer
     */
    public function testEscapeInteger()
    {
      $this->assertEquals("'123'", $this->connection->escape(123));
      $this->assertEquals("'-123'", $this->connection->escape(-123));
    }

    /**
     * Test escape float
     */
    public function testEscapeFloat()
    {
      $this->assertEquals("'123.456'", $this->connection->escape(123.456));
      $this->assertEquals("'-123.456'", $this->connection->escape(-123.456));
    }

    /**
     * Test escape DateTime instance
     */
    public function testEscapeDateTime()
    {
      $this->assertEquals("'2015-08-11 23:05:28'", $this->connection->escape(new DateTime('2015-08-11 23:05:28', new DateTimeZone('GMT'))));
    }

    /**
     * Test escape array
     */
    public function testEscapeArray()
    {
      $this->assertEquals("('1','2','3')", $this->connection->escape([ 1, 2, 3 ]));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnEmptyArray()
    {
      $this->connection->escape([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionOnUsupportedObject()
    {
      $this->connection->escape($this->link);
    }
  }