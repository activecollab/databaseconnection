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
use DateTime;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class UpdateTest extends TestCase
{
    /**
     * @var MysqliConnection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);

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
    public function tearDown()
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        parent::tearDown();
    }

    /**
     * Test if affected rows returns the correct value.
     */
    public function testAffectedRows()
    {
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));

        $this->connection->execute('UPDATE `writers` SET `name` = ? WHERE `name` = ?', 'Lev Nikolayevich Tolstoy', 'Leo Tolstoy');
        $this->assertEquals(1, $this->connection->affectedRows());

        $this->connection->execute('UPDATE `writers` SET `name` = ? WHERE `name` = ?', 'Nothing to Update', 'Leo Tolstoy');
        $this->assertEquals(0, $this->connection->affectedRows());
    }

    /**
     * Test update all records.
     */
    public function testUpdate()
    {
        $affected_rows = $this->connection->update('writers', [
            'name' => 'Anton Chekhov',
            'birthday' => new DateTime('1860-01-29'),
        ]);

        $this->assertEquals(3, $affected_rows);
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Anton Chekhov'));
    }

    /**
     * Test update with prepared conditions.
     */
    public function testUpdateWithPreparedConditions()
    {
        $affected_rows = $this->connection->update('writers', [
            'name' => 'Anton Chekhov',
            'birthday' => new DateTime('1860-01-29'),
        ], "`name` = 'Leo Tolstoy'");

        $this->assertEquals(1, $affected_rows);
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Anton Chekhov'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
    }

    /**
     * Test update prepared conditions, as array.
     */
    public function testUpdateWithPreparedConditionsAsOnlyElement()
    {
        $affected_rows = $this->connection->update('writers', [
            'name' => 'Anton Chekhov',
            'birthday' => new DateTime('1860-01-29'),
        ], ["`name` = 'Leo Tolstoy'"]);

        $this->assertEquals(1, $affected_rows);
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Anton Chekhov'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
    }

    /**
     * Test update where conditions are an array that needs to be prepared.
     */
    public function testUpdateWithConditionsThatNeedToBePrepared()
    {
        $affected_rows = $this->connection->update('writers', [
            'name' => 'Anton Chekhov',
            'birthday' => new DateTime('1860-01-29'),
        ], ['`name` = ?', 'Leo Tolstoy']);

        $this->assertEquals(1, $affected_rows);
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Anton Chekhov'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionDueToEmptyConditionsArray()
    {
        $this->connection->update('writers', [
        'name' => 'Anton Chekhov',
        'birthday' => new DateTime('1860-01-29'),
        ], []);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionDueToInvalidConditions()
    {
        $this->connection->update('writers', [
            'name' => 'Anton Chekhov',
            'birthday' => new DateTime('1860-01-29'),
        ], 123);
    }
}
