<?php

namespace ActiveCollab\DatabaseConnection\Test;

use mysqli;
use RuntimeException;

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
