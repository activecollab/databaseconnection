<?php

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
     * Set up test environment
     */
    public function setUp()
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
     * Tear down the test environment
     */
    public function tearDown()
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers1`');
        $this->connection->execute('DROP TABLE IF EXISTS `writers2`');
        $this->connection->execute('DROP TABLE IF EXISTS `writers3`');

        parent::tearDown();
    }

    /**
     * Test list all database tables
     */
    public function testListTables()
    {
        $this->assertEquals(['writers1', 'writers2', 'writers3'], $this->connection->getTableNames());
    }

    /**
     * Test if we can check if table exists or not
     */
    public function testTableExists()
    {
        $this->assertTrue($this->connection->tableExists('writers1'));
        $this->assertFalse($this->connection->tableExists('writersXYZ'));
    }

    /**
     * Test drop table command
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
