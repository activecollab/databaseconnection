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

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\Test\Base\DbLinkedTestCase;

class DatabasesTest extends DbLinkedTestCase
{
    /**
     * @var MysqliConnection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);
    }

    /**
     * Test database exists call.
     */
    public function testDatabaseExists()
    {
        $this->assertTrue($this->connection->databaseExists('activecollab_database_connection_test'));
        $this->assertFalse($this->connection->databaseExists('this one does not exist'));
    }

    /**
     * Test drop database.
     */
    public function testDropDatabase()
    {
        $this->connection->execute('CREATE DATABASE activecollab_database_connection_test_create');

        $this->assertTrue($this->connection->databaseExists('activecollab_database_connection_test_create'));
        $this->connection->dropDatabase('activecollab_database_connection_test_create');
        $this->assertFalse($this->connection->databaseExists('activecollab_database_connection_test_create'));
    }
}
