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

use mysqli;
use RuntimeException;

abstract class DbLinkedTestCase extends TestCase
{
    protected mysqli $link;

    public function setUp(): void
    {
        parent::setUp();

        $this->link = new mysqli(
            'localhost',
            'root',
            $this->getValidMySqlPassword(),
            'activecollab_database_connection_test'
        );

        if ($this->link->connect_error) {
            throw new RuntimeException('Failed to connect to database. MySQL said: '.$this->link->connect_error);
        }
    }

    public function tearDown(): void
    {
        $this->link->close();

        parent::tearDown();
    }

    protected function getValidMySqlPassword(): string
    {
        return (string) getenv('DATABASE_CONNECTION_TEST_PASSWORD');
    }
}
