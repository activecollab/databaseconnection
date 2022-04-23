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

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\Test\Base\DbLinkedTestCase;
use DateTime;

class CountTest extends DbLinkedTestCase
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

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateTime('1828-09-09'), 'Alexander Pushkin', new DateTime('1799-06-06'), 'Fyodor Dostoyevsky', new DateTime('1821-11-11'));
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void
    {
        $this->connection->execute('DROP TABLE `writers`');

        parent::tearDown();
    }

    /**
     * Test cound all records in the table.
     */
    public function testCountAll()
    {
        $this->assertSame(3, $this->connection->count('writers'));
    }

    /**
     * Test cound with conditions provided as string.
     */
    public function testCountWithStringConditions()
    {
        $this->assertSame(1, $this->connection->count('writers', "`name` = 'Leo Tolstoy'"));
    }

    /**
     * Test count with conditions that need to be prepared first.
     */
    public function testCountWithConditionsThatNeedToBePrepared()
    {
        $this->assertSame(1, $this->connection->count('writers', ['name = ?', 'Leo Tolstoy']));
    }

    /**
     * Test count(*).
     */
    public function testCountAsterisk()
    {
        $this->assertSame(3, $this->connection->count('writers', null, '*'));
    }

    /**
     * Test count by field.
     */
    public function testCountField()
    {
        $this->assertSame(3, $this->connection->count('writers', null, 'name'));
    }
}
