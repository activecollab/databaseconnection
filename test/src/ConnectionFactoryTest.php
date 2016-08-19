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
use ActiveCollab\DatabaseConnection\ConnectionFactory;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class ConnectionFactoryTest extends TestCase
{
    /**
     * Test mysqli connection.
     */
    public function testMysqli()
    {
        $connection = (new ConnectionFactory())->mysqli('localhost', 'root', '', 'activecollab_database_connection_test');

        $this->assertInstanceOf(MysqliConnection::class, $connection);
        $connection->disconnect();
    }

    /**
     * Test MySQLi connection with port added to hostname.
     */
    public function testMysqliWithPortInHostname()
    {
        $connection = (new ConnectionFactory())->mysqli('localhost:3306', 'root', '', 'activecollab_database_connection_test');

        $this->assertInstanceOf(MysqliConnection::class, $connection);
        $connection->disconnect();
    }
}
