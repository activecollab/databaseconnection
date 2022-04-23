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
use ActiveCollab\DatabaseConnection\Exception\QueryException;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;
use DateTime;

class ExecuteTest extends DbConnectedTestCase
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

    public function testExceptionOnInvalidQuery()
    {
        $this->expectException(QueryException::class);
        $this->connection->execute('invalid query 100%');
    }

    /**
     * Test execute.
     */
    public function testExecute()
    {
        $result = $this->connection->execute('SELECT * FROM `writers` ORDER BY `id`');

        $this->assertInstanceOf('\ActiveCollab\DatabaseConnection\Result\Result', $result);
        $this->assertCount(3, $result);

        $writers = [];

        foreach ($result as $row) {
            $writers[] = $row;
        }

        $this->assertCount(3, $writers);

        $this->assertEquals([
            'id' => 1,
            'name' => 'Leo Tolstoy',
            'birthday' => '1828-09-09',
        ], $writers[0]);

        $this->assertEquals([
            'id' => 2,
            'name' => 'Alexander Pushkin',
            'birthday' => '1799-06-06',
        ], $writers[1]);

        $this->assertEquals([
            'id' => 3,
            'name' => 'Fyodor Dostoyevsky',
            'birthday' => '1821-11-11',
        ], $writers[2]);
    }

    /**
     * Test execute.
     */
    public function testExecuteWithCustomCaster()
    {
        $result = $this->connection->execute('SELECT * FROM `writers` ORDER BY `id`');

        $this->assertInstanceOf('\ActiveCollab\DatabaseConnection\Result\Result', $result);
        $this->assertCount(3, $result);

        $caster = new ValueCaster(['id' => ValueCaster::CAST_STRING, 'birthday' => ValueCaster::CAST_DATE]);

        $this->assertEquals(ValueCaster::CAST_STRING, $caster->getTypeByFieldName('id'));
        $this->assertEquals(ValueCaster::CAST_DATE, $caster->getTypeByFieldName('birthday'));

        $result->setValueCaster($caster);

        $writers = [];

        foreach ($result as $row) {
            $writers[] = $row;
        }

        $this->assertCount(3, $writers);

        $this->assertSame('1', $writers[0]['id']);
        $this->assertSame('Leo Tolstoy', $writers[0]['name']);
        $this->assertSame('1828-09-09', $writers[0]['birthday']->format('Y-m-d'));

        $this->assertSame('2', $writers[1]['id']);
        $this->assertSame('Alexander Pushkin', $writers[1]['name']);
        $this->assertSame('1799-06-06', $writers[1]['birthday']->format('Y-m-d'));

        $this->assertSame('3', $writers[2]['id']);
        $this->assertSame('Fyodor Dostoyevsky', $writers[2]['name']);
        $this->assertSame('1821-11-11', $writers[2]['birthday']->format('Y-m-d'));
    }

    /**
     * Execute first cell.
     */
    public function testExecuteFirstCell()
    {
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Alexander Pushkin'));
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Fyodor Dostoyevsky'));

        $this->assertEquals('Leo Tolstoy', $this->connection->executeFirstCell('SELECT `name` FROM `writers` WHERE `id` = ?', 1));
        $this->assertEquals('Alexander Pushkin', $this->connection->executeFirstCell('SELECT `name` FROM `writers` WHERE `id` = ?', 2));
        $this->assertEquals('Fyodor Dostoyevsky', $this->connection->executeFirstCell('SELECT `name` FROM `writers` WHERE `id` = ?', 3));
    }

    /**
     * Test execute first row.
     */
    public function testExecuteFirstRow()
    {
        $this->assertEquals([
            'id' => 1,
            'name' => 'Leo Tolstoy',
            'birthday' => '1828-09-09',
        ], $this->connection->executeFirstRow('SELECT * FROM `writers` ORDER BY `id`'));
    }

    /**
     * Test execute first column.
     */
    public function testExecuteFirstColumn()
    {
        $this->assertEquals([
            'Alexander Pushkin',
            'Fyodor Dostoyevsky',
            'Leo Tolstoy',
        ], $this->connection->executeFirstColumn('SELECT `name` FROM `writers` ORDER BY `name`'));

        $this->assertEquals([
            '1799-06-06',
            '1821-11-11',
            '1828-09-09',
        ], $this->connection->executeFirstColumn('SELECT `birthday` FROM `writers` ORDER BY `name`'));
    }
}
