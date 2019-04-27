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

namespace ActiveCollab\DatabaseConnection;

use ActiveCollab\DatabaseConnection\BatchInsert\BatchInsertInterface;
use ActiveCollab\DatabaseConnection\Exception\QueryException;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;

interface ConnectionInterface
{
    /**
     * Load mode.
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
     * Return method for DB results.
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
     * Insert mode, used by insert() method.
     */
    const INSERT = 'INSERT';
    const REPLACE = 'REPLACE';

    /**
     * Close connection.
     */
    public function disconnect();

    /**
     * Execute a query and return a result.
     *
     * @param  string                    $sql
     * @param  mixed[]                   $arguments
     * @return ResultInterface|true|null
     */
    public function execute($sql, ...$arguments);

    /**
     * Return first row that provided SQL query returns.
     *
     * @param  string  $sql
     * @param  mixed[] $arguments
     * @return array
     */
    public function executeFirstRow($sql, ...$arguments);

    /**
     * Return value from the first cell of each column that provided SQL query returns.
     *
     * @param  string  $sql
     * @param  mixed[] $arguments
     * @return array
     */
    public function executeFirstColumn($sql, ...$arguments);

    /**
     * Return value from the first cell of the first row that provided SQL query returns.
     *
     * @param  string  $sql
     * @param  mixed[] $arguments
     * @return mixed
     */
    public function executeFirstCell($sql, ...$arguments);

    /**
     * Prepare and execute query, while letting the developer change the load and return modes.
     *
     * @param  string                  $sql
     * @param  mixed                   $arguments
     * @param  int                     $load_mode
     * @param  int                     $return_mode
     * @param  string                  $return_class_or_field
     * @param  array|null              $constructor_arguments
     * @param  ContainerInterface|null $container
     * @return mixed
     * @throws QueryException
     */
    public function advancedExecute($sql, $arguments = null, $load_mode = self::LOAD_ALL_ROWS, $return_mode = self::RETURN_ARRAY, $return_class_or_field = null, array $constructor_arguments = null, ContainerInterface &$container = null);

    /**
     * Prepare and execute SELECT query.
     *
     * @param  string                    $table_name
     * @param  array|string|null         $fields
     * @param  array|string|null         $conditions
     * @param  array|string|null         $order_by_fields
     * @return ResultInterface|null|true
     */
    public function select($table_name, $fields = null, $conditions = null, $order_by_fields = null);

    /**
     * Prepare and execute SELECT query, and return the first row.
     *
     * @param  string            $table_name
     * @param  array|string|null $fields
     * @param  array|string|null $conditions
     * @param  array|string|null $order_by_fields
     * @return array
     */
    public function selectFirstRow($table_name, $fields = null, $conditions = null, $order_by_fields = null);

    /**
     * Prepare and execute SELECT query, and return the first column of the first row.
     *
     * @param  string            $table_name
     * @param  array|string|null $fields
     * @param  array|string|null $conditions
     * @param  array|string|null $order_by_fields
     * @return array
     */
    public function selectFirstCell($table_name, $fields = null, $conditions = null, $order_by_fields = null);

    /**
     * Prepare and execute SELECT query, and return the first column.
     *
     * @param  string            $table_name
     * @param  array|string|null $fields
     * @param  array|string|null $conditions
     * @param  array|string|null $order_by_fields
     * @return array
     */
    public function selectFirstColumn($table_name, $fields = null, $conditions = null, $order_by_fields = null);

    /**
     * Return number of records from $table_name that match $conditions.
     *
     * Fields that COUNT() targets can be specified after $conditions. If they are omitted, COUNT(`id`) will be ran
     *
     * @param  string            $table_name
     * @param  array|string|null $conditions
     * @param  string            $field
     * @return int
     */
    public function count(string $table_name, $conditions = null, $field = 'id'): int;

    /**
     * Insert into $table a row that is reperesented with $values (key is field name, and value is value that we need to set).
     *
     * @param  string $table_name
     * @param  array  $field_value_map
     * @param  string $mode
     * @return int
     * @throws InvalidArgumentException
     */
    public function insert(
        string $table_name,
        array $field_value_map,
        string $mode = ConnectionInterface::INSERT
    ): int;

    public function batchInsert(
        string $table_name,
        array $fields,
        int $rows_per_batch = 50,
        string $mode = self::INSERT
    ): BatchInsertInterface;

    public function lastInsertId(): int;

    /**
     * Update one or more rows with the given list of values for fields.
     *
     * $conditions can be a string, or an array where first element is a patter and other elements are arguments
     *
     * @param  string                   $table_name
     * @param  array                    $field_value_map
     * @param  string|array|null        $conditions
     * @return int
     * @throws InvalidArgumentException
     */
    public function update($table_name, array $field_value_map, $conditions = null): int;

    /**
     * Delete one or more records from the table.
     *
     * $conditions can be a string, or an array where first element is a patter and other elements are arguments
     *
     * @param  string                   $table_name
     * @param  string|array|null        $conditions
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($table_name, $conditions = null): int;
    public function affectedRows(): int;

    public function transact(callable $body, callable $on_success = null, callable $on_error = null): void;
    public function beginWork(): void;
    public function commit(): void;
    public function rollback(): void;
    public function inTransaction(): bool;

    public function executeFromFile(string $file_path): void;
    public function databaseExists(string $database_name): bool;
    public function createDatabase(string $database_name): void;
    public function dropDatabase(string $database_name): void;

    /**
     * @param  string $user_name
     * @return bool
     */
    public function userExists($user_name);

    /**
     * @param string $user_name
     * @param string $hostname
     */
    public function dropUser($user_name, $hostname = '%');

    /**
     * Return array of table names.
     *
     * @param  string $database_name
     * @return array
     */
    public function getTableNames($database_name = '');

    /**
     * Return true if table named $table_name exists in the selected database.
     *
     * @param  string $table_name
     * @return bool
     */
    public function tableExists($table_name);

    /**
     * Drop a table named $table_name from selected database.
     *
     * @param string $table_name
     */
    public function dropTable($table_name);

    /**
     * Return a list of field name.
     *
     * @param  string $table_name
     * @return array
     */
    public function getFieldNames($table_name);

    /**
     * Return true if $field_name exists in $table_name.
     *
     * @param string $table_name
     * @param string $field_name
     */
    public function fieldExists($table_name, $field_name);

    /**
     * Drop a field from the database.
     *
     * @param string $table_name
     * @param string $field_name
     * @param bool   $check_if_exists
     */
    public function dropField($table_name, $field_name, $check_if_exists = true);

    /**
     * Return a list of index names for the given table.
     *
     * @param  string $table_name
     * @return array
     */
    public function getIndexNames($table_name);

    /**
     * Return true if index exists in the table.
     *
     * @param string $table_name
     * @param string $index_name
     */
    public function indexExists($table_name, $index_name);

    /**
     * Drop an individual index.
     *
     * @param string $table_name
     * @param string $index_name
     * @param bool   $check_if_exists
     */
    public function dropIndex($table_name, $index_name, $check_if_exists = true);

    /**
     * Return true if foreign key checks are on.
     *
     * @return bool
     */
    public function areForeignKeyChecksOn();

    /**
     * Turn on FK checks.
     */
    public function turnOnForeignKeyChecks();

    /**
     * Turn off FK checks.
     */
    public function turnOffForeignKeyChecks();

    /**
     * Return a list of FK-s for a given table.
     *
     * @param  string $table_name
     * @return array
     */
    public function getForeignKeyNames($table_name);

    /**
     * Return true if foreign key exists in a given table.
     *
     * @param  string $table_name
     * @param  string $fk_name
     * @return bool
     */
    public function foreignKeyExists($table_name, $fk_name);

    /**
     * Drop a foreign key.
     *
     * @param string $table_name
     * @param string $fk_name
     * @param bool   $check_if_exists
     */
    public function dropForeignKey($table_name, $fk_name, $check_if_exists = true);

    /**
     * Prepare SQL (replace ? with data from $arguments array).
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return string
     */
    public function prepare($sql, ...$arguments);

    /**
     * Prepare conditions and return them as string.
     *
     * @param  array|string|null $conditions
     * @return string
     */
    public function prepareConditions($conditions);

    /**
     * Escape string before we use it in query...
     *
     * @param  mixed                    $unescaped
     * @return string
     * @throws InvalidArgumentException
     */
    public function escapeValue($unescaped);

    /**
     * Escape table field name.
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeFieldName($unescaped);

    /**
     * Escape table name.
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeTableName($unescaped);

    /**
     * Escape database name.
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeDatabaseName($unescaped);

    // ---------------------------------------------------
    //  Events
    // ---------------------------------------------------

    /**
     * Set a callback that will receive every query after we run it.
     *
     * Callback should accept two parameters: first for SQL that was ran, and second for time that it took to run
     *
     * @param callable|null $callback
     */
    public function onLogQuery(callable $callback = null);
}
