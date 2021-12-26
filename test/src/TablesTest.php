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
class TablesTest extends TestCase
{
    /**
     * @var MysqliConnection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);

        $this->connection->execute('DROP TABLE IF EXISTS `writers1`');
        $this->connection->execute("CREATE TABLE `writers1` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->connection->execute('DROP TABLE IF EXISTS `writers2`');
        $this->connection->execute("CREATE TABLE `writers2` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->connection->execute('DROP TABLE IF EXISTS `writers3`');
        $this->connection->execute("CREATE TABLE `writers3` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers1`');
        $this->connection->execute('DROP TABLE IF EXISTS `writers2`');
        $this->connection->execute('DROP TABLE IF EXISTS `writers3`');

        parent::tearDown();
    }

    /**
     * Test list all database tables.
     */
    public function testListTables()
    {
        $this->assertEquals(['writers1', 'writers2', 'writers3'], $this->connection->getTableNames());
    }

    /**
     * Test list tables from a specified database (user needs to have proper permissions over that database).
     */
    public function testListTablesFromAnotherDatabase()
    {
        if ($this->connection->databaseExists('test_another_database')) {
            $this->connection->execute('DROP DATABASE `test_another_database`');
        }

        $this->connection->execute('CREATE DATABASE `test_another_database`');
        $this->assertTrue($this->connection->databaseExists('test_another_database'));

        $this->connection->execute('CREATE TABLE `test_another_database`.`test_table` (id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT)');

        $this->assertEquals(['test_table'], $this->connection->getTableNames('test_another_database'));

        $this->connection->execute('DROP DATABASE `test_another_database`');
        $this->assertFalse($this->connection->databaseExists('test_another_database'));
    }

    /**
     * Test if we can check if table exists or not.
     */
    public function testTableExists()
    {
        $this->assertTrue($this->connection->tableExists('writers1'));
        $this->assertFalse($this->connection->tableExists('writersXYZ'));
    }

    /**
     * Test drop table command.
     */
    public function testDropTable()
    {
        $this->assertEquals(['writers1', 'writers2', 'writers3'], $this->connection->getTableNames());
        $this->assertTrue($this->connection->tableExists('writers2'));

        $this->connection->dropTable('writers2');

        $this->assertEquals(['writers1', 'writers3'], $this->connection->getTableNames());
        $this->assertFalse($this->connection->tableExists('writers2'));
    }
}
