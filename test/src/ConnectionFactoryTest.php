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
     * @expectedException \ActiveCollab\DatabaseConnection\Exception\ConnectionException
     * @expectedExceptionMessage MySQLi connection failed
     */
    public function testExceptionOnInvalidArguments()
    {
        (new ConnectionFactory())->mysqli(
            'localhost',
            'unknonw-user',
            '',
            'activecollab_database_connection_test'
        );
    }

    public function testMysqli()
    {
        $connection = (new ConnectionFactory())->mysqli(
            'localhost',
            'root',
            $this->getValidMySqlPassword(),
            'activecollab_database_connection_test'
        );

        $this->assertInstanceOf(MysqliConnection::class, $connection);
        $connection->disconnect();
    }

    public function testMysqliWithPortInHostname()
    {
        $connection = (new ConnectionFactory())->mysqli(
            'localhost:3306',
            'root',
            $this->getValidMySqlPassword(),
            'activecollab_database_connection_test'
        );

        $this->assertInstanceOf(MysqliConnection::class, $connection);
        $connection->disconnect();
    }
}
