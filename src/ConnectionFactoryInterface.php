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

interface ConnectionFactoryInterface
{
    /**
     * Connect to MySQL using mysqli extension.
     *
     * @param  string           $host
     * @param  string           $user
     * @param  string           $pass
     * @param  string           $select_database
     * @param  string|null      $set_connection_encoding
     * @param  bool             $set_connection_encoding_with_query
     * @return MysqliConnection
     */
    public function mysqli($host, $user, $pass, $select_database = '', $set_connection_encoding = null, $set_connection_encoding_with_query = false);
}
