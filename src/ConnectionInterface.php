<?php

namespace ActiveCollab\DatabaseConnection;

use ActiveCollab\DatabaseConnection\Exception\Query;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseConnection\BatchInsert\BatchInsertInterface;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Closure;
use Exception;

/**
 * @package ActiveCollab\DatabaseConnection
 */
interface ConnectionInterface
{
    /**
     * Load mode
     *
     * LOAD_ALL_ROWS - Load all rows
     * LOAD_FIRST_ROW - Limit result set to first row and load it
     * LOAD_FIRST_COLUMN - Return content of first column
     * LOAD_FIRST_CELL - Load only first cell of first row
     */
    const LOAD_ALL_ROWS = 0;
    const LOAD_FIRST_ROW = 1;
    const LOAD_FIRST_COLUMN = 2;
    const LOAD_FIRST_CELL = 3;

    /**
     * Return method for DB results
     *
     * RETURN_ARRAY - Return fields as associative array
     * RETURN_OBJECT_BY_CLASS - Create new object instance and hydrate it
     * RETURN_OBJECT_BY_FIELD - Read class from record field, create instance
     *   and hydrate it
     */
    const RETURN_ARRAY = 0;
    const RETURN_OBJECT_BY_CLASS = 1;
    const RETURN_OBJECT_BY_FIELD = 2;

    /**
     * Insert mode, used by insert() method
     */
    const INSERT = 'INSERT';
    const REPLACE = 'REPLACE';

    /**
     * Execute a query and return a result
     *
     * @param  string                    $sql
     * @param  mixed                     ...$arguments
     * @return ResultInterface|true|null
     */
    public function execute($sql, ...$arguments);

    /**
     * Return first row that provided SQL query returns
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return array
     */
    public function executeFirstRow($sql, ...$arguments);

    /**
     * Return value from the first cell of each column that provided SQL query returns
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return array
     */
    public function executeFirstColumn($sql, ...$arguments);

    /**
     * Return value from the first cell of the first row that provided SQL query returns
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public function executeFirstCell($sql, ...$arguments);

    /**
     * Prepare and execute query, while letting the developer change the load and return modes
     *
     * @param  string                  $sql
     * @param  mixed                   $arguments
     * @param  int                     $load_mode
     * @param  int                     $return_mode
     * @param  string                  $return_class_or_field
     * @param  array|null              $constructor_arguments
     * @param  ContainerInterface|null $container
     * @return mixed
     * @throws Query
     */
    public function advancedExecute($sql, $arguments = null, $load_mode = self::LOAD_ALL_ROWS, $return_mode = self::RETURN_ARRAY, $return_class_or_field = null, array $constructor_arguments = null, ContainerInterface &$container = null);

    /**
     * Return number of records from $table_name that match $conditions
     *
     * Fields that COUNT() targets can be specified after $conditions. If they are omitted, COUNT(`id`) will be ran
     *
     * @param  string            $table_name
     * @param  array|string|null $conditions
     * @param  string            $field
     * @return integer
     */
    public function count($table_name, $conditions = null, $field = 'id');

    /**
     * Insert into $table a row that is reperesented with $values (key is field name, and value is value that we need to set)
     *
     * @param  string $table
     * @param  array  $field_value_map
     * @param  string $mode
     * @return int
     * @throws InvalidArgumentException
     */
    public function insert($table, array $field_value_map, $mode = self::INSERT);

    /**
     * Prepare a batch insert utility instance
     *
     * @param  string               $table_name
     * @param  array                $fields
     * @param  int                  $rows_per_batch
     * @param  string               $mode
     * @return BatchInsertInterface
     */
    public function batchInsert($table_name, array $fields, $rows_per_batch = 50, $mode = self::INSERT);

    /**
     * Return last insert ID
     *
     * @return integer
     */
    public function lastInsertId();

    /**
     * Update one or more rows with the given list of values for fields
     *
     * $conditions can be a string, or an array where first element is a patter and other elements are arguments
     *
     * @param  string                   $table_name
     * @param  array                    $field_value_map
     * @param  string|array|null        $conditions
     * @return int
     * @throws InvalidArgumentException
     */
    public function update($table_name, array $field_value_map, $conditions = null);

    /**
     * Delete one or more records from the table
     *
     * $conditions can be a string, or an array where first element is a patter and other elements are arguments
     *
     * @param  string            $table_name
     * @param  string|array|null $conditions
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($table_name, $conditions = null);

    /**
     * Return number of affected rows
     *
     * @return integer
     */
    public function affectedRows();

    /**
     * Run body commands within a transation
     *
     * @param  Closure      $body
     * @param  Closure|null $on_success
     * @param  CLosure|null $on_error
     * @throws Exception
     */
    public function transact(Closure $body, $on_success = null, $on_error = null);

    /**
     * Begin transaction
     */
    public function beginWork();

    /**
     * Commit transaction
     */
    public function commit();

    /**
     * Rollback transaction
     */
    public function rollback();

    /**
     * Return true if system is in transaction
     *
     * @return boolean
     */
    public function inTransaction();

    /**
     * @param  string  $database_name
     * @return boolean
     */
    public function databaseExists($database_name);

    /**
     * @param  string  $user_name
     * @return boolean
     */
    public function userExists($user_name);

    /**
     * Return true if table named $table_name exists in the selected database
     *
     * @param  string $table_name
     * @return bool
     */
    public function tableExists($table_name);

    /**
     * Return array of table names
     *
     * @param  string $database_name
     * @return array
     */
    public function getTableNames($database_name = '');

    /**
     * Drop a table named $table_name from selected database
     *
     * @param string $table_name
     */
    public function dropTable($table_name);

    /**
     * Prepare SQL (replace ? with data from $arguments array)
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return string
     */
    public function prepare($sql, ...$arguments);

    /**
     * Prepare conditions and return them as string
     *
     * @param  array|string|null $conditions
     * @return string
     */
    public function prepareConditions($conditions);

    /**
     * Escape string before we use it in query...
     *
     * @param  mixed $unescaped
     * @return string
     * @throws InvalidArgumentException
     */
    public function escapeValue($unescaped);

    /**
     * Escape table field name
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeFieldName($unescaped);

    /**
     * Escape table name
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeTableName($unescaped);

    // ---------------------------------------------------
    //  Events
    // ---------------------------------------------------

    /**
     * Set a callback that will receive every query after we run it
     *
     * Callback should accept two parameters: first for SQL that was ran, and second for time that it took to run
     *
     * @param callable|null $callback
     */
    public function onLogQuery(callable $callback = null);
}
