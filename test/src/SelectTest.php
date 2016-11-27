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
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use DateTime;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class SelectTest extends TestCase
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
    public function tearDown()
    {
        $this->connection->execute('DROP TABLE `writers`');

        parent::tearDown();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Table name is required
     */
    public function testTableNameIsRequired()
    {
        $this->connection->select('');
    }

    public function testSelectAll()
    {
        $result = $this->connection->select('writers');

        $this->assertInstanceOf(ResultInterface::class, $result);
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

    public function testSelectSpecificField()
    {
        $result = $this->connection->select('writers', 'id');

        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertCount(3, $result);

        $writers = [];

        foreach ($result as $row) {
            $writers[] = $row;
        }

        $this->assertCount(3, $writers);

        $this->assertEquals(['id' => 1], $writers[0]);
        $this->assertEquals(['id' => 2], $writers[1]);
        $this->assertEquals(['id' => 3], $writers[2]);
    }

    public function testSelectSpecificFields()
    {
        $result = $this->connection->select('writers', ['id', 'name']);

        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertCount(3, $result);

        $writers = [];

        foreach ($result as $row) {
            $writers[] = $row;
        }

        $this->assertCount(3, $writers);

        $this->assertEquals(['id' => 1, 'name' => 'Leo Tolstoy'], $writers[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Alexander Pushkin'], $writers[1]);
        $this->assertEquals(['id' => 3, 'name' => 'Fyodor Dostoyevsky'], $writers[2]);
    }

    public function testSelectWithConditions()
    {
        $result = $this->connection->select('writers', null, ['birthday >= ?', '1821-11-11']);

        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertCount(2, $result);
    }

    public function testOrderBy()
    {
        $result = $this->connection->select('writers', 'name', null, 'name');
        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertCount(3, $result);

        $writers = [];

        foreach ($result as $row) {
            $writers[] = $row;
        }

        $this->assertCount(3, $writers);

        $this->assertEquals('Alexander Pushkin', $writers[0]['name']);
        $this->assertEquals('Fyodor Dostoyevsky', $writers[1]['name']);
        $this->assertEquals('Leo Tolstoy', $writers[2]['name']);
    }

    public function testSelectFirstCell()
    {
        $this->assertEquals(1, $this->connection->selectFirstCell('writers', 'id', ['`name` = ?', 'Leo Tolstoy']));
        $this->assertEquals(2, $this->connection->selectFirstCell('writers', 'id', ['`name` = ?', 'Alexander Pushkin']));
        $this->assertEquals(3, $this->connection->selectFirstCell('writers', 'id', ['`name` = ?', 'Fyodor Dostoyevsky']));

        $this->assertEquals('Leo Tolstoy', $this->connection->selectFirstCell('writers', 'name', ['`id` = ?', 1]));
        $this->assertEquals('Alexander Pushkin', $this->connection->selectFirstCell('writers', 'name', ['`id` = ?', 2]));
        $this->assertEquals('Fyodor Dostoyevsky', $this->connection->selectFirstCell('writers', 'name', ['`id` = ?', 3]));
    }

    public function testSelectFirstRow()
    {
        $first_writer_by_id = $this->connection->selectFirstRow('writers');
        $this->assertInternalType('array', $first_writer_by_id);

        $this->assertEquals(['id' => 1, 'name' => 'Leo Tolstoy', 'birthday' => '1828-09-09'], $first_writer_by_id);

        $first_writer_by_name = $this->connection->selectFirstRow('writers', null, null, 'name');
        $this->assertInternalType('array', $first_writer_by_name);
        $this->assertEquals(['id' => 2, 'name' => 'Alexander Pushkin', 'birthday' => '1799-06-06'], $first_writer_by_name);
    }

    public function testExecuteFirstColumn()
    {
        $writer_names = $this->connection->selectFirstColumn('writers', 'name', null, 'name');
        $this->assertInternalType('array', $writer_names);
        $this->assertEquals(['Alexander Pushkin', 'Fyodor Dostoyevsky', 'Leo Tolstoy'], $writer_names);

        $writer_birthdays = $this->connection->selectFirstColumn('writers', 'birthday', null, 'name');
        $this->assertInternalType('array', $writer_birthdays);
        $this->assertEquals(['1799-06-06', '1821-11-11', '1828-09-09'], $writer_birthdays);
    }
}
