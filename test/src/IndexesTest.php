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

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class IndexesTest extends TestCase
{
    /**
     * @var MysqliConnection
     */
    private $connection;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);

        $this->connection->execute('DROP TABLE IF EXISTS `writers`');
        $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`),
            KEY (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        parent::tearDown();
    }

    /**
     * Test get index names.
     */
    public function testGetIndexNames()
    {
        $index_names = $this->connection->getIndexNames('writers');

        $this->assertInternalType('array', $index_names);
        $this->assertCount(2, $index_names);
        $this->assertEquals(['PRIMARY', 'name'], $index_names);
    }

    /**
     * Test index exists.
     */
    public function testIndexExists()
    {
        $this->assertTrue($this->connection->indexExists('writers', 'name'));
        $this->assertFalse($this->connection->indexExists('writers', 'unknown index'));
    }

    /**
     * Test drop index.
     */
    public function testDropIndex()
    {
        $this->assertContains('name', $this->connection->getIndexNames('writers'));
        $this->assertCount(2, $this->connection->getIndexNames('writers'));

        $this->connection->dropIndex('writers', 'name');

        $this->assertNotContains('name', $this->connection->getIndexNames('writers'));
        $this->assertCount(1, $this->connection->getIndexNames('writers'));
    }

    /**
     * Test safe drop of non-existing index.
     */
    public function testSafeDropNonExistingIndex()
    {
        $this->assertCount(2, $this->connection->getIndexNames('writers'));
        $this->connection->dropIndex('writers', 'index that does not exist');
        $this->assertCount(2, $this->connection->getIndexNames('writers'));
    }

    /**
     * @expectedException \ActiveCollab\DatabaseConnection\Exception\QueryException
     * @expectedExceptionMessage Can't DROP 'index that does not exist'; check that column/key exists
     */
    public function testUnsafeDropNonExistingIndex()
    {
        $this->assertCount(2, $this->connection->getIndexNames('writers'));
        $this->connection->dropIndex('writers', 'index that does not exist', false);
        $this->assertCount(2, $this->connection->getIndexNames('writers'));
    }
}
