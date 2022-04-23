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

use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;

class UsersTest extends DbConnectedTestCase
{
    /**
     * Test user exists call.
     */
    public function testUserExists()
    {
        $this->assertTrue($this->connection->userExists('root'));
        $this->assertFalse($this->connection->databaseExists('this one does not exist'));
    }

    /**
     * Test drop user account.
     */
    public function testDropUser()
    {
        $this->connection->execute('CREATE USER ?@? IDENTIFIED BY ?', 'monty', '%', 'some_pass');

        $this->assertTrue($this->connection->userExists('monty'));
        $this->connection->dropUser('monty');
        $this->assertFalse($this->connection->userExists('monty'));
    }
}
