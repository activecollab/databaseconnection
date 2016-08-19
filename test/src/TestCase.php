<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Test;

use mysqli;
use RuntimeException;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var mysqli
     */
    protected $link;

    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->link = new mysqli('localhost', 'root', '', 'activecollab_database_connection_test');

        if ($this->link->connect_error) {
            throw new RuntimeException('Failed to connect to database. MySQL said: '.$this->link->connect_error);
        }
    }

    /**
     * Tear down test environment.
     */
    public function tearDown()
    {
        $this->link->close();

        parent::tearDown();
    }
}
