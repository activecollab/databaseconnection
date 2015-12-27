<?php

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class DatabasesTest extends TestCase
{
    /**
     * @var MysqliConnection
     */
    private $connection;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);
    }

    /**
     * Test database exists call
     */
    public function testDatabaseExists()
    {
        $this->assertTrue($this->connection->databaseExists('activecollab_database_connection_test'));
        $this->assertFalse($this->connection->databaseExists('this one does not exist'));
    }
}
