<?php

namespace ActiveCollab\DatabaseConnection;

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\Exception\ConnectionException;
use mysqli as MysqliLink;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseConnection
 */
class ConnectionFactory
{
    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @param LoggerInterface|null $log
     */
    public function __construct(LoggerInterface &$log = null)
    {
        $this->log = $log;
    }

    /**
     * Connect to MySQL using mysqli extension
     *
     * @param  string           $host
     * @param  string           $user
     * @param  string           $pass
     * @param  string           $select_database
     * @param  string|null      $set_connection_encoding
     * @return MysqliConnection
     * @throws ConnectionException
     */
    public function mysqli($host, $user, $pass, $select_database = '', $set_connection_encoding = null)
    {
        $connection = new MysqliConnection($this->mysqliConnectFromParams($host, $user, $pass), $this->log);

        if ($select_database) {
            $connection->setDatabaseName($select_database);
        }

        if ($set_connection_encoding) {
            $connection->execute('SET NAMES ' . $set_connection_encoding);
        }

        return $connection;
    }

    /**
     * @param  string     $host
     * @param  string     $user
     * @param  string     $pass
     * @param  string     $select_database
     * @return MysqliLink
     * @throws ConnectionException
     */
    private function mysqliConnectFromParams($host, $user, $pass, $select_database = '')
    {
        $link = new MysqliLink($host, $user, $pass);

        if ($link->connect_error) {
            throw new ConnectionException('Failed to connect to database. MySQL said: ' . $link->connect_error);
        }

        if ($select_database) {
            if ($link->select_db($select_database)) {
                return $link;
            } else {
                throw new ConnectionException("Failed to select database '$select_database'");
            }
        } else {
            return $link;
        }
    }
}
