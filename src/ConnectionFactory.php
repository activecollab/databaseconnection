<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
     * {@inheritdoc}
     */
    public function mysqli($host, $user, $pass, $select_database = '', $set_connection_encoding = null, $set_connection_encoding_with_query = false)
    {
        try {
            if (strpos($host, ':') !== false) {
                $host_bits = explode(':', $host);

                if (empty($host_bits[1])) {
                    $host_bits[1] = 3306;
                }

                $link = $this->mysqliConnectFromParams($host_bits[0], (int) $host_bits[1], $user, $pass);
            } else {
                $link = $this->mysqliConnectFromParams($host, 3306, $user, $pass);
            }
        } catch (\Exception $e) {
            throw new ConnectionException('MySQLi connection failed: ' . $e->getMessage(), $e->getCode(), $e);
        }

        if ($set_connection_encoding && !$set_connection_encoding_with_query) {
            $link->set_charset($set_connection_encoding);
        }

        $connection = new MysqliConnection($link, $this->log);

        if ($select_database) {
            $connection->setDatabaseName($select_database);
        }

        if ($set_connection_encoding && $set_connection_encoding_with_query) {
            $connection->execute('SET NAMES ' . $set_connection_encoding);
        }

        return $connection;
    }

    /**
     * @param  string              $host
     * @param  int                 $port
     * @param  string              $user
     * @param  string              $pass
     * @param  string              $select_database
     * @return MysqliLink
     * @throws ConnectionException
     */
    private function mysqliConnectFromParams($host, $port, $user, $pass, $select_database = '')
    {
        $link = new MysqliLink($host, $user, $pass, '', $port);

        if ($link->connect_error) {
            throw new ConnectionException('Failed to connect to database. MySQL said: ' . $link->connect_error);
        }

        if ($select_database && !$link->select_db($select_database)) {
            throw new ConnectionException("Failed to select database '$select_database'");
        }

        return $link;
    }
}
