<?php

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\ConnectionFactory;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class ConnectionFactoryTest extends TestCase
{
    /**
     * Test mysqli connection
     */
    public function testMysqli()
    {
        $connection = (new ConnectionFactory())->mysqli('localhost', 'root', '', 'activecollab_database_connection_test');

        $this->assertInstanceOf(MysqliConnection::class, $connection);
        $connection->disconnect();
    }
}
