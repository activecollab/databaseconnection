<?php

namespace ActiveCollab\DatabaseConnection;

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;

/**
 * @package ActiveCollab\DatabaseConnection
 */
interface ConnectionFactoryInterface
{
    /**
     * Connect to MySQL using mysqli extension
     *
     * @param  string           $host
     * @param  string           $user
     * @param  string           $pass
     * @param  string           $select_database
     * @param  string|null      $set_connection_encoding
     * @param  boolean          $set_connection_encoding_with_query
     * @return MysqliConnection
     */
    public function mysqli($host, $user, $pass, $select_database = '', $set_connection_encoding = null, $set_connection_encoding_with_query = false);
}
