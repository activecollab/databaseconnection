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

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class UsersTest extends TestCase
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
     * Test user exists call.
     */
    public function testUserExists()
    {
        $this->assertTrue($this->connection->userExists('root'));
        $this->assertFalse($this->connection->databaseExists('this one does not exist'));
    }

    /**
     * Test drop user account.
     */
    public function testDropUser()
    {
        $this->connection->execute('CREATE USER ?@? IDENTIFIED BY ?', 'monty', '%', 'some_pass');

        $this->assertTrue($this->connection->userExists('monty'));
        $this->connection->dropUser('monty');
        $this->assertFalse($this->connection->userExists('monty'));
    }
}
