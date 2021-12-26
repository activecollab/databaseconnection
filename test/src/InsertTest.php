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
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use DateTime;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class InsertTest extends TestCase
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

        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateTime('1828-09-09'), 'Alexander Pushkin', new DateTime('1799-06-06'), 'Fyodor Dostoyevsky', new DateTime('1821-11-11'));

        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        parent::tearDown();
    }

    /**
     * Test if last insert ID returns correct value.
     */
    public function testInsertId()
    {
        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?)', 'Anton Chekhov', new DateTime('1860-01-29'));
        $this->assertEquals(4, $this->connection->lastInsertId());
    }

    /**
     * Test insert.
     */
    public function testInsert()
    {
        $last_insert_id = $this->connection->insert('writers', [
            'name' => 'Anton Chekhov',
            'birthday' => new DateTime('1860-01-29'),
        ]);

        $this->assertEquals(4, $last_insert_id);
        $this->assertEquals(4, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $this->assertEquals([
            'id' => 4,
            'name' => 'Anton Chekhov',
            'birthday' => '1860-01-29',
        ], $this->connection->executeFirstRow('SELECT * FROM `writers` WHERE `id` = ?', $last_insert_id));
    }

    /**
     * Test replace mode.
     */
    public function testReplace()
    {
        $last_insert_id = $this->connection->insert('writers', [
            'id' => 1,
            'name' => 'Anton Chekhov',
            'birthday' => new DateTime('1860-01-29'),
        ], ConnectionInterface::REPLACE);

        $this->assertEquals(1, $last_insert_id);
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $this->assertEquals([
            'id' => 1,
            'name' => 'Anton Chekhov',
            'birthday' => '1860-01-29',
        ], $this->connection->executeFirstRow('SELECT * FROM `writers` WHERE `id` = ?', $last_insert_id));
    }
}
