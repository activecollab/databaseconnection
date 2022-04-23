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

namespace ActiveCollab\DatabaseConnection\Test\Base;

use ActiveCollab\DatabaseConnection\ConnectionFactory;
use ActiveCollab\DatabaseConnection\ConnectionInterface;

class DbConnectedTestCase extends DbLinkedTestCase
{
    protected ConnectionInterface $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = (new ConnectionFactory())
            ->mysqliFromLink($this->link);
    }
}
