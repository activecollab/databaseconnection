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

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;
use RuntimeException;

class ExecuteFromFileTest extends DbConnectedTestCase
{
    private MysqliConnection $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);

        if ($this->connection->tableExists('currencies')) {
            $this->connection->dropTable('currencies');
        }

        $this->connection->execute("CREATE TABLE IF NOT EXISTS `currencies` (
            `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `name` VARCHAR(191) DEFAULT NULL,
            `code` VARCHAR(3) NOT NULL DEFAULT '',
            `symbol` VARCHAR(5) DEFAULT NULL,
            `symbol_native` VARCHAR(5) DEFAULT NULL,
            `decimal_spaces` TINYINT UNSIGNED NOT NULL DEFAULT '2',
            `decimal_rounding` DECIMAL(4, 3) NOT NULL DEFAULT '0',
            `is_default` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            `updated_at` DATETIME,
            PRIMARY KEY (`id`),
            UNIQUE `code` (`code`),
            INDEX `updated_at` (`updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function tearDown(): void
    {
        if ($this->connection->tableExists('currencies')) {
            $this->connection->dropTable('currencies');
        }

        parent::tearDown();
    }

    public function testExecuteFromNonExistingFile()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("File not found");
        $unknown_file = dirname(__DIR__) . '/resources/unknown file';
        $this->assertFileDoesNotExist($unknown_file);

        $this->connection->executeFromFile($unknown_file);
    }

    public function testExecuteFromFile()
    {
        $this->assertSame(0, $this->connection->count('currencies'));
        $this->connection->executeFromFile(dirname(__DIR__) . '/resources/currencies.sql');
        $this->assertSame(121, $this->connection->count('currencies'));
    }
}
