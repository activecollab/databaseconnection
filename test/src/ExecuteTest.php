<?php

  namespace ActiveCollab\DatabaseConnection\Test;

  use ActiveCollab\DatabaseConnection\Connection;

  /**
   * @package ActiveCollab\DatabaseConnection\Test
   */
  class ExecuteTest extends TestCase
  {
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up test environment
     */
    public function setUp()
    {
      parent::setUp();

      $this->connection = new Connection($this->link);
    }

    /**
     * @expectedException \ActiveCollab\DatabaseConnection\Exception\Query
     */
    public function testExceptionOnInvalidQuery()
    {
      $this->connection->execute('invalid query 100%');
    }

    /**
     * Test create table
     */
    public function testCreateTable()
    {
      $create_table = $this->connection->execute("CREATE TABLE `memories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
        `value` mediumtext COLLATE utf8mb4_unicode_ci,
        `updated_on` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `key` (`key`)
      ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

      $this->assertTrue($create_table);
    }
  }