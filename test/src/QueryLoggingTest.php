<?php

  namespace ActiveCollab\DatabaseConnection\Test;

  use ActiveCollab\DatabaseConnection\Connection;

  /**
   * @package ActiveCollab\DatabaseConnection\Test
   */
  class QueryLoggingTest extends TestCase
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
     * Test if callback query log callback is working properly
     */
    public function testQueryLogCallback()
    {
      $log = [];

      $this->connection->onLogQuery(function($sql, $execution_time) use (&$log) {
        $log[] = [ 'sql' => $sql, 'exec_time' => $execution_time ];
      });

      $this->connection->execute('SHOW TABLES LIKE ?', 'my_awesome_table_prefix_%');

      $this->assertCount(1, $log);
      $this->assertNotEmpty($log[0]['exec_time']);
      $this->assertEquals("SHOW TABLES LIKE 'my_awesome_table_prefix_%'", $log[0]['sql']);
    }
  }