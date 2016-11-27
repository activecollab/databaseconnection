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
use DateTime;
use Exception;
use RuntimeException;

class TransactionsTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = new Connection($this->link);

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
    public function tearDown()
    {
        if ($this->connection->inTransaction()) {
            $this->connection->rollback();
        }

        $this->connection->execute('DROP TABLE `writers`');

        parent::tearDown();
    }

    /**
     * Test if in transaction flag works well in multi-level transactions.
     */
    public function testInTransactionInMultiLevelTransaction()
    {
        $this->assertFalse($this->connection->inTransaction());

        // First level
        $this->connection->beginWork();
        $this->assertTrue($this->connection->inTransaction());

        // Second level
        $this->connection->beginWork();
        $this->assertTrue($this->connection->inTransaction());

        // Close first level
        $this->connection->commit();
        $this->assertTrue($this->connection->inTransaction());

        // Close second level
        $this->connection->commit();
        $this->assertFalse($this->connection->inTransaction());
    }

    /**
     * Test if rollback rolls-back multi-level transaction.
     */
    public function testRollbackMultipleLevels()
    {
        $this->assertFalse($this->connection->inTransaction());

        // First level
        $this->connection->beginWork();
        $this->assertTrue($this->connection->inTransaction());

        // Second level
        $this->connection->beginWork();
        $this->assertTrue($this->connection->inTransaction());

        // Rollback everything
        $this->connection->rollback();
        $this->assertFalse($this->connection->inTransaction());
    }

    /**
     * Test commit.
     */
    public function testCommit()
    {
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $this->connection->beginWork();
        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?)', 'Anton Chekhov', new DateTime('1860-01-29'));
        $this->connection->commit();

        $this->assertEquals(4, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Test rollback.
     */
    public function testRollback()
    {
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $this->connection->beginWork();
        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?)', 'Anton Chekhov', new DateTime('1860-01-29'));
        $this->connection->rollback();

        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Test if transaction closure executes properly.
     */
    public function testTransactClosure()
    {
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $this->connection->transact(function () {
            $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?)', 'Anton Chekhov', new DateTime('1860-01-29'));
        });

        $this->assertEquals(4, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Test if transaction closure propagates exception thrown within.
     *
     * @expectedException \RuntimeException
     */
    public function testRollbackTransactClosure()
    {
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        $this->connection->transact(function () {
            $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?)', 'Anton Chekhov', new DateTime('1860-01-29'));
            throw new RuntimeException('Throwing an exception here');
        });
    }

    /**
     * Test successfull transaction callback.
     */
    public function testSuccessfulTransactionCallback()
    {
        $is_success = false;

        $this->connection->transact(
            function () {
                $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?)', 'Anton Chekhov', new DateTime('1860-01-29'));
            },
            function () use (&$is_success) {
                $is_success = true;
            }
        );

        $this->assertTrue($is_success);
        $this->assertEquals(4, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Test error in transcation callback.
     */
    public function testErrorInTransactionCallback()
    {
        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));

        /** @var Exception $exception_caught */
        $exception_caught = false;

        $this->connection->transact(
            function () {
                $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?)', 'Anton Chekhov', new DateTime('1860-01-29'));
                throw new RuntimeException('Throwing an exception here');
            },
            null,
            function (Exception $e) use (&$exception_caught) {
                $exception_caught = $e;
            }
        );

        $this->assertInstanceOf('\RuntimeException', $exception_caught);
        $this->assertEquals('Throwing an exception here', $exception_caught->getMessage());

        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }
}
