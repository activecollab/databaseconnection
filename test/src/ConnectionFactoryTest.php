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
use ActiveCollab\DatabaseConnection\ConnectionFactory;
use ActiveCollab\DatabaseConnection\Exception\ConnectionException;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;

class ConnectionFactoryTest extends DbConnectedTestCase
{
    public function testExceptionOnInvalidArguments()
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage("Failed to select database 'activecollab_database_connection_test'");

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
