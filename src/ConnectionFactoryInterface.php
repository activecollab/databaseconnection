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
use mysqli as MysqliLink;

interface ConnectionFactoryInterface
{
    public function mysqli(
        string $host,
        string $user,
        string $pass,
        string $select_database = '',
        string $set_connection_encoding = null,
        bool $set_connection_encoding_with_query = false
    ): MysqliConnection;

    public function mysqliFromLink(
        MysqliLink $link,
        string $select_database = '',
        string $set_connection_encoding = null,
        bool $set_connection_encoding_with_query = false
    ): MysqliConnection;
}
