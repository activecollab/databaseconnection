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

namespace ActiveCollab\DatabaseConnection;

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\Exception\ConnectionException;
use Exception;
use mysqli as MysqliLink;
use Psr\Log\LoggerInterface;

class ConnectionFactory
{
    public function __construct(
        private ?LoggerInterface $log = null
    )
    {
    }

    public function mysqli(
        string $host,
        string $user,
        string $pass,
        string $select_database = '',
        string $set_connection_encoding = null,
        bool $set_connection_encoding_with_query = false
    ): MysqliConnection
    {
        try {
            if (str_contains($host, ':')) {
                $host_bits = explode(':', $host);

                if (empty($host_bits[1])) {
                    $host_bits[1] = 3306;
                }

                $link = $this->mysqliConnectFromParams($host_bits[0], (int) $host_bits[1], $user, $pass);
            } else {
                $link = $this->mysqliConnectFromParams($host, 3306, $user, $pass);
            }
        } catch (Exception $e) {
            throw new ConnectionException(
                sprintf('MySQLi connection failed: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        if ($set_connection_encoding && !$set_connection_encoding_with_query) {
            $link->set_charset($set_connection_encoding);
        }

        return $this->mysqliFromLink(
            $link,
            $select_database,
            $set_connection_encoding,
            $set_connection_encoding_with_query
        );
    }

    public function mysqliFromLink(
        MysqliLink $link,
        string $select_database = '',
        string $set_connection_encoding = null,
        bool $set_connection_encoding_with_query = false
    ): MysqliConnection
    {
        $connection = new MysqliConnection($link, $this->log);

        if ($select_database) {
            $connection->setDatabaseName($select_database);
        }

        if ($set_connection_encoding && $set_connection_encoding_with_query) {
            $connection->execute('SET NAMES ' . $set_connection_encoding);
        }

        return $connection;
    }

    private function mysqliConnectFromParams(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $select_database = ''
    ): MysqliLink
    {
        try {
            $link = new MysqliLink($host, $user, $pass, '', $port);
        } catch (Exception $e) {
            throw new ConnectionException(
                sprintf('MySQLi connection failed: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        if ($link->connect_error) {
            throw new ConnectionException('Failed to connect to database. MySQL said: ' . $link->connect_error);
        }

        if ($select_database && !$link->select_db($select_database)) {
            throw new ConnectionException("Failed to select database '$select_database'");
        }

        return $link;
    }
}
