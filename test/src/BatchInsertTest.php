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

use ActiveCollab\DatabaseConnection\BatchInsert\BatchInsert;
use ActiveCollab\DatabaseConnection\BatchInsert\BatchInsertInterface;
use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;
use DateTime;
use RuntimeException;

class BatchInsertTest extends DbConnectedTestCase
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
     * Test if connection is properly creating batch insert instance.
     */
    public function testConnectionPropertyCreatesBatchInsertObject()
    {
        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday']);

        $this->assertInstanceOf(BatchInsertInterface::class, $batch_insert);

        $this->assertEquals('writers', $batch_insert->getTableName());
        $this->assertEquals(['name', 'birthday'], $batch_insert->getFields());
        $this->assertEquals(50, $batch_insert->getRowsPerBatch());
        $this->assertEquals(ConnectionInterface::INSERT, $batch_insert->getMode());
    }

    /**
     * Test if rows per batch and insert mode can be changed.
     */
    public function testRowsPerBatchAndModeCanBeChanged()
    {
        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 125, ConnectionInterface::REPLACE);

        $this->assertInstanceOf(BatchInsertInterface::class, $batch_insert);

        $this->assertEquals('writers', $batch_insert->getTableName());
        $this->assertEquals(['name', 'birthday'], $batch_insert->getFields());
        $this->assertEquals(125, $batch_insert->getRowsPerBatch());
        $this->assertEquals(ConnectionInterface::REPLACE, $batch_insert->getMode());
    }

    /**
     * Test if insert SQL foundation is properly prepared.
     */
    public function testInsertSqlIsProperlyPrepared()
    {
        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday']);

        $property = (new \ReflectionClass(BatchInsert::class))->getProperty('sql_foundation');
        $property->setAccessible(true);

        $sql_foundation = $property->getValue($batch_insert);

        $this->assertEquals('INSERT INTO `writers` (`name`, `birthday`) VALUES ', $sql_foundation);
    }

    /**
     * Test if replace SQL foundation is properly prepared.
     */
    public function testReplaceSqlIsProperlyPrepared()
    {
        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 50, ConnectionInterface::REPLACE);

        $property = (new \ReflectionClass(BatchInsert::class))->getProperty('sql_foundation');
        $property->setAccessible(true);

        $sql_foundation = $property->getValue($batch_insert);

        $this->assertEquals('REPLACE INTO `writers` (`name`, `birthday`) VALUES ', $sql_foundation);
    }

    /**
     * Test if row prepare pattern is proeprly prepared.
     */
    public function testRowPreparePattern()
    {
        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 50, ConnectionInterface::REPLACE);

        $property = (new \ReflectionClass(BatchInsert::class))->getProperty('row_prepare_pattern');
        $property->setAccessible(true);

        $row_prepare_pattern = $property->getValue($batch_insert);

        $this->assertEquals('(?, ?)', $row_prepare_pattern);
    }

    /**
     * Test batch insert.
     */
    public function testBatchInsert()
    {
        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 3);
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->insert('Leo Tolstoy', new DateTime('1828-09-09'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->insert('Alexander Pushkin', new DateTime('1799-06-06'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->insert('Fyodor Dostoyevsky', new DateTime('1821-11-11'));
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->insert('Anton Chekhov', new DateTime('1860-01-29'));
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->done();
        $this->assertEquals(4, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Test batch replace.
     */
    public function testBatchReplace()
    {
        $this->connection->execute('ALTER TABLE `writers` ADD UNIQUE INDEX `name` (`name`);');

        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 3);
        $batch_insert->insert('Leo Tolstoy', new DateTime('1828-09-09'));
        $batch_insert->insert('Alexander Pushkin', new DateTime('1799-06-06'));
        $batch_insert->insert('Fyodor Dostoyevsky', new DateTime('1821-11-11'));
        $batch_insert->done();

        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
        $this->assertEquals(1, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Alexander Pushkin'));
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Fyodor Dostoyevsky'));

        $batch_replace = $this->connection->batchInsert('writers', ['name', 'birthday'], 3, ConnectionInterface::REPLACE);
        $batch_replace->insert('Leo Tolstoy', new DateTime('1828-09-09'));
        $batch_replace->insert('Alexander Pushkin', new DateTime('1799-06-06'));
        $batch_replace->insert('Fyodor Dostoyevsky', new DateTime('1821-11-11'));
        $batch_replace->done();

        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
        $this->assertEquals(4, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
        $this->assertEquals(5, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Alexander Pushkin'));
        $this->assertEquals(6, $this->connection->executeFirstCell('SELECT `id` FROM `writers` WHERE `name` = ?', 'Fyodor Dostoyevsky'));
    }

    /**
     * Test flush.
     */
    public function testFlush()
    {
        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 350);

        $batch_insert->insert('Leo Tolstoy', new DateTime('1828-09-09'));
        $batch_insert->insert('Alexander Pushkin', new DateTime('1799-06-06'));

        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->flush();

        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Test if rows can't be inserted once batch is done.
     *
     *
     */
    public function testRowsCantBeInsertedOnceDone()
    {
        $this->expectException(RuntimeException::class);

        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 350);

        $batch_insert->insert('Leo Tolstoy', new DateTime('1828-09-09'));
        $batch_insert->insert('Alexander Pushkin', new DateTime('1799-06-06'));

        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->done();

        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->insert('Fyodor Dostoyevsky', new DateTime('1821-11-11'));
    }

    /**
     * Test if rows can't be inserted escaped once batch is done.
     *
     *
     */
    public function testRowsCantBeInsertedEscapedOnceDone()
    {
        $this->expectException(RuntimeException::class);

        $batch_insert = $this->connection->batchInsert('writers', ['name', 'birthday'], 350);

        $batch_insert->insert('Leo Tolstoy', new DateTime('1828-09-09'));
        $batch_insert->insert('Alexander Pushkin', new DateTime('1799-06-06'));

        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->done();

        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $batch_insert->insertEscaped($this->connection->escapeValue('Fyodor Dostoyevsky'), $this->connection->escapeValue(new DateTime('1821-11-11')));
    }
}
