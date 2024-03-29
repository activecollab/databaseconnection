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

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Result\Result;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;
use ActiveCollab\DatabaseConnection\Test\Fixture\Writer;
use DateTime;
use InvalidArgumentException;

class ExecuteLoadObjectTest extends DbConnectedTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateTime('1828-09-09'), 'Alexander Pushkin', new DateTime('1799-06-06'), 'Fyodor Dostoyevsky', new DateTime('1821-11-11'));
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void
    {
        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        parent::tearDown();
    }

    public function testExceptionWhenLoadingByObjectClassAndClassNameIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_CLASS);
    }

    public function testExceptionWhenLoadingByFieldAndFieldNameIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_FIELD);
    }

    /**
     * Test if objects are properly create by known class name.
     */
    public function testExecuteLoadObjectFromClassName()
    {
        $result = $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_CLASS, Writer::class);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertCount(3, $result);

        /** @var \ActiveCollab\DatabaseConnection\Test\Fixture\Writer[] $writers */
        $writers = [];

        foreach ($result as $row) {
            $writers[] = $row;
        }

        $this->assertCount(3, $writers);

        $this->assertInstanceOf('ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writers[0]);
        $this->assertEquals(1, $writers[0]->getId());
        $this->assertEquals('Leo Tolstoy', $writers[0]->getName());
        $this->assertEquals('1828-09-09', $writers[0]->getBirthday()->format('Y-m-d'));

        $this->assertInstanceOf('ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writers[1]);
        $this->assertEquals(2, $writers[1]->getId());
        $this->assertEquals('Alexander Pushkin', $writers[1]->getName());
        $this->assertEquals('1799-06-06', $writers[1]->getBirthday()->format('Y-m-d'));

        $this->assertInstanceOf('ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writers[2]);
        $this->assertEquals(3, $writers[2]->getId());
        $this->assertEquals('Fyodor Dostoyevsky', $writers[2]->getName());
        $this->assertEquals('1821-11-11', $writers[2]->getBirthday()->format('Y-m-d'));
    }

    /**
     * Test if constructor arguments are properly set to instances when provided.
     */
    public function testExecuteLoadObjectWithConstructorArguments()
    {
        $result = $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_CLASS, '\ActiveCollab\DatabaseConnection\Test\Fixture\Writer');

        $this->assertInstanceOf('\ActiveCollab\DatabaseConnection\Result\Result', $result);
        $this->assertCount(3, $result);

        /** @var Writer $writer */
        foreach ($result as $writer) {
            $this->assertInstanceOf('\ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writer);
            $this->assertNull($writer->constructor_argument_1);
            $this->assertNull($writer->constructor_argument_2);
        }

        $result = $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_CLASS, '\ActiveCollab\DatabaseConnection\Test\Fixture\Writer', [12, 34]);

        $this->assertInstanceOf('\ActiveCollab\DatabaseConnection\Result\Result', $result);
        $this->assertCount(3, $result);

        /** @var Writer $writer */
        foreach ($result as $writer) {
            $this->assertInstanceOf('\ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writer);
            $this->assertSame(12, $writer->constructor_argument_1);
            $this->assertSame(34, $writer->constructor_argument_2);
        }
    }

    /**
     * Test if objects are properly created by value stored in a field.
     */
    public function testExecuteLoadObjectFromField()
    {
        // ---------------------------------------------------
        //  Add type field
        // ---------------------------------------------------

        $this->connection->execute('ALTER TABLE `writers` ADD `type` VARCHAR(255)  NULL DEFAULT ? AFTER `id`;', '\\ActiveCollab\\DatabaseConnection\\Test\\Fixture\\Writer');

        $this->assertEquals(
            [
                'id' => 1,
                'type' => '\ActiveCollab\DatabaseConnection\Test\Fixture\Writer',
                'name' => 'Leo Tolstoy',
                'birthday' => '1828-09-09',
            ],
            $this->connection->executeFirstRow('SELECT * FROM `writers` WHERE `id` = ?', 1)
        );

        // ---------------------------------------------------
        //  Use type field to know which objects to create
        // ---------------------------------------------------

        $result = $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_FIELD, 'type');

        $this->assertInstanceOf('\ActiveCollab\DatabaseConnection\Result\Result', $result);
        $this->assertCount(3, $result);

        /** @var \ActiveCollab\DatabaseConnection\Test\Fixture\Writer[] $writers */
        $writers = [];

        foreach ($result as $row) {
            $writers[] = $row;
        }

        $this->assertCount(3, $writers);

        $this->assertInstanceOf('ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writers[0]);
        $this->assertEquals(1, $writers[0]->getId());
        $this->assertEquals('Leo Tolstoy', $writers[0]->getName());
        $this->assertEquals('1828-09-09', $writers[0]->getBirthday()->format('Y-m-d'));

        $this->assertInstanceOf('ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writers[1]);
        $this->assertEquals(2, $writers[1]->getId());
        $this->assertEquals('Alexander Pushkin', $writers[1]->getName());
        $this->assertEquals('1799-06-06', $writers[1]->getBirthday()->format('Y-m-d'));

        $this->assertInstanceOf('ActiveCollab\DatabaseConnection\Test\Fixture\Writer', $writers[2]);
        $this->assertEquals(3, $writers[2]->getId());
        $this->assertEquals('Fyodor Dostoyevsky', $writers[2]->getName());
        $this->assertEquals('1821-11-11', $writers[2]->getBirthday()->format('Y-m-d'));
    }
}
