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

use ActiveCollab\DatabaseConnection\Connection;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class PrepareTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new Connection($this->link);
    }

    /**
     * Test that prepare work when there are no arguments.
     */
    public function testPrepareOnlyPattern()
    {
        $this->assertEquals('SHOW TABLES;', $this->connection->prepare('SHOW TABLES;'));
    }

    /**
     * Test prepare full query.
     */
    public function testPrepare()
    {
        $this->assertEquals("SELECT * FROM awesome_songs WHERE artist = 'Iron?Maiden' AND album = 'Fear of?the Dark' AND song = 'Childhood\\'s End'", $this->connection->prepare('SELECT * FROM awesome_songs WHERE artist = ? AND album = ? AND song = ?', 'Iron?Maiden', 'Fear of?the Dark', "Childhood's End"));
    }

    /**
     * Test prepare partial.
     */
    public function testPreparePartial()
    {
        $this->assertEquals("WHERE id IN ('1','2','3') AND NOT id = '12'", $this->connection->prepare('WHERE id IN ? AND NOT id = ?', [1, 2, 3], 12));
    }
}
