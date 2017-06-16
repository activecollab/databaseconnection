<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Connection;

use ActiveCollab\DatabaseConnection\BatchInsert\BatchInsert;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Exception\ConnectionException;
use ActiveCollab\DatabaseConnection\Exception\QueryException;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseConnection\Result\Result;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DateValue\DateValue;
use Closure;
use DateTime;
use Exception;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use mysqli;
use mysqli_result;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @package ActiveCollab\DatabaseConnection
 */
class MysqliConnection implements ConnectionInterface
{
    /**
     * @var MysqliConnection
     */
    private $link;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var string
     */
    private $database_name;

    /**
     * @param mysqli               $link
     * @param LoggerInterface|null $log
     */
    public function __construct(mysqli $link, LoggerInterface &$log = null)
    {
        $this->link = $link;
        $this->log = $log;
    }

    /**
     * Set database name and optionally select that database.
     *
     * @param  string              $database_name
     * @param  bool|true           $select_database
     * @return $this
     * @throws ConnectionException
     */
    public function &setDatabaseName($database_name, $select_database = true)
    {
        if (empty($select_database) || $this->link->select_db($database_name)) {
            $this->database_name = $database_name;
        } else {
            throw new ConnectionException("Failed to select database '$database_name'");
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->link->close();
    }

    /**
     * {@inheritdoc}
     */
    public function execute($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_ALL_ROWS);
    }

    /**
     * {@inheritdoc}
     */
    public function executeFirstRow($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_FIRST_ROW);
    }

    /**
     * {@inheritdoc}
     */
    public function executeFirstColumn($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_FIRST_COLUMN);
    }

    /**
     * {@inheritdoc}
     */
    public function executeFirstCell($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_FIRST_CELL);
    }

    /**
     * {@inheritdoc}
     */
    public function advancedExecute($sql, $arguments = null, $load_mode = ConnectionInterface::LOAD_ALL_ROWS, $return_mode = ConnectionInterface::RETURN_ARRAY, $return_class_or_field = null, array $constructor_arguments = null, ContainerInterface &$container = null)
    {
        if ($return_mode == ConnectionInterface::RETURN_OBJECT_BY_CLASS && empty($return_class_or_field)) {
            throw new InvalidArgumentException('Class is required');
        } elseif ($return_mode == ConnectionInterface::RETURN_OBJECT_BY_FIELD && empty($return_class_or_field)) {
            throw new InvalidArgumentException('Field name is required');
        }

        $query_result = $this->prepareAndExecuteQuery($sql, $arguments);

        if ($query_result === false) {
            $query_result = $this->tryToRecoverFromFailedQuery($sql, $arguments, $load_mode, $return_mode);
        }

        if ($query_result instanceof mysqli_result) {
            if ($query_result->num_rows > 0) {
                switch ($load_mode) {
                    case ConnectionInterface::LOAD_FIRST_ROW:
                        $result = $query_result->fetch_assoc();
                        $this->getDefaultCaster()->castRowValues($result);

                        break;

                    case ConnectionInterface::LOAD_FIRST_COLUMN:
                        $result = [];

                        while ($row = $query_result->fetch_assoc()) {
                            foreach ($row as $k => $v) {
                                $result[] = $this->getDefaultCaster()->castValue($k, $v);
                                break; // Done after first cell in a row
                            }
                        }

                        break;

                    case ConnectionInterface::LOAD_FIRST_CELL:
                        $result = null;

                        foreach ($query_result->fetch_assoc() as $k => $v) {
                            $result = $this->getDefaultCaster()->castValue($k, $v);
                            break; // Done after first cell
                        }

                        break;
                    default:
                        return new Result($query_result, $return_mode, $return_class_or_field, $constructor_arguments, $container); // Don't close result, we need it
                }
            } else {
                $result = null;
            }

            $query_result->close();

            return $result;
        } elseif ($query_result === true) {
            return true;
        } else {
            throw new QueryException($this->link->error, $this->link->errno);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function select($table_name, $fields = null, $conditions = null, $order_by_fields = null)
    {
        return $this->execute($this->prepareSelectQueryFromArguments($table_name, $fields, $conditions, $order_by_fields));
    }

    /**
     * {@inheritdoc}
     */
    public function selectFirstRow($table_name, $fields = null, $conditions = null, $order_by_fields = null)
    {
        return $this->executeFirstRow($this->prepareSelectQueryFromArguments($table_name, $fields, $conditions, $order_by_fields));
    }

    /**
     * {@inheritdoc}
     */
    public function selectFirstColumn($table_name, $fields = null, $conditions = null, $order_by_fields = null)
    {
        return $this->executeFirstColumn($this->prepareSelectQueryFromArguments($table_name, $fields, $conditions, $order_by_fields));
    }

    /**
     * {@inheritdoc}
     */
    public function selectFirstCell($table_name, $fields = null, $conditions = null, $order_by_fields = null)
    {
        return $this->executeFirstCell($this->prepareSelectQueryFromArguments($table_name, $fields, $conditions, $order_by_fields));
    }

    /**
     * Prepare SELECT query from arguments used by select*() methods.
     *
     * @param  string            $table_name
     * @param  array|string|null $fields
     * @param  array|string|null $conditions
     * @param  array|string|null $order_by_fields
     * @return string
     */
    private function prepareSelectQueryFromArguments($table_name, $fields, $conditions = null, $order_by_fields = null)
    {
        if (empty($table_name)) {
            throw new InvalidArgumentException('Table name is required');
        }

        $escaped_field_names = empty($fields) ? '*' : implode(',', array_map(function ($field_name) {
            return $this->escapeFieldName($field_name);
        }, (array) $fields));

        if ($conditions) {
            $where = 'WHERE ' . $this->prepareConditions($conditions);
        } else {
            $where = '';
        }

        $escaped_order_by_field_names = '';

        if (!empty($order_by_fields)) {
            $escaped_order_by_field_names = implode(',', array_map(function ($field_name) {
                return $this->escapeFieldName($field_name);
            }, (array) $order_by_fields));
        }

        $order_by = $escaped_order_by_field_names ? "ORDER BY $escaped_order_by_field_names" : '';

        return trim("SELECT $escaped_field_names FROM {$this->escapeTableName($table_name)} $where $order_by");
    }

    /**
     * {@inheritdoc}
     */
    public function count($table_name, $conditions = null, $field = 'id')
    {
        if (empty($table_name)) {
            throw new InvalidArgumentException('Table name is required');
        }

        if (empty($field)) {
            throw new InvalidArgumentException('Field name is required');
        }

        if ($conditions) {
            $where = ' WHERE ' . $this->prepareConditions($conditions);
        } else {
            $where = '';
        }

        $count = $field == '*' ? 'COUNT(*)' : 'COUNT(' . $this->escapeFieldName($field) . ')';

        return $this->executeFirstCell("SELECT $count AS 'row_count' FROM " . $this->escapeTableName($table_name) . $where);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $field_value_map, $mode = ConnectionInterface::INSERT)
    {
        if (empty($field_value_map)) {
            throw new InvalidArgumentException("Values array can't be empty");
        }

        $mode = strtoupper($mode);

        if ($mode != ConnectionInterface::INSERT && $mode != ConnectionInterface::REPLACE) {
            throw new InvalidArgumentException("Mode '$mode' is not a valid insert mode");
        }

        $this->execute("$mode INTO " . $this->escapeTableName($table) . ' (' . implode(',', array_map(function ($field_name) {
            return $this->escapeFieldName($field_name);
        }, array_keys($field_value_map))) . ') VALUES (' . implode(',', array_map(function ($value) {
            return $this->escapeValue($value);
        }, $field_value_map)) . ')');

        return $this->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function batchInsert($table_name, array $fields, $rows_per_batch = 50, $mode = self::INSERT)
    {
        return new BatchInsert($this, $table_name, $fields, $rows_per_batch, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId()
    {
        return $this->link->insert_id;
    }

    /**
     * {@inheritdoc}
     */
    public function update($table_name, array $field_value_map, $conditions = null)
    {
        if (empty($field_value_map)) {
            throw new InvalidArgumentException("Values array can't be empty");
        }

        if ($conditions = $this->prepareConditions($conditions)) {
            $conditions = " WHERE $conditions";
        }

        $this->execute('UPDATE ' . $this->escapeTableName($table_name) . ' SET ' . implode(',', array_map(function ($field_name, $value) {
            return $this->escapeFieldName($field_name) . ' = ' . $this->escapeValue($value);
        }, array_keys($field_value_map), $field_value_map)) . $conditions);

        return $this->affectedRows();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table_name, $conditions = null)
    {
        if ($conditions = $this->prepareConditions($conditions)) {
            $this->execute("DELETE FROM {$this->escapeTableName($table_name)} WHERE $conditions");
        } else {
            $this->execute("DELETE FROM {$this->escapeTableName($table_name)}");
        }

        return $this->affectedRows();
    }

    /**
     * {@inheritdoc}
     */
    public function affectedRows()
    {
        return $this->link->affected_rows;
    }

    /**
     * {@inheritdoc}
     */
    public function transact(Closure $body, $on_success = null, $on_error = null)
    {
        if ($body instanceof Closure) {
            try {
                $this->beginWork();
                call_user_func($body);
                $this->commit();

                if ($on_success instanceof Closure) {
                    call_user_func($on_success);
                }
            } catch (Exception $e) {
                $this->rollback();

                if ($on_error instanceof Closure) {
                    call_user_func($on_error, $e);
                } else {
                    throw $e;
                }
            }
        } else {
            throw new InvalidArgumentException('Closure expected');
        }
    }

    /**
     * Transaction level.
     *
     * @var int
     */
    private $transaction_level = 0;

    /**
     * {@inheritdoc}
     */
    public function beginWork()
    {
        if ($this->transaction_level == 0) {
            $this->execute('BEGIN WORK');
        }
        ++$this->transaction_level;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        if ($this->transaction_level) {
            --$this->transaction_level;
            if ($this->transaction_level == 0) {
                $this->execute('COMMIT');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        if ($this->transaction_level) {
            $this->transaction_level = 0;
            $this->execute('ROLLBACK');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction()
    {
        return $this->transaction_level > 0;
    }

    /**
     * @param string $file_path
     */
    public function executeFromFile($file_path)
    {
        if (!is_file($file_path)) {
            throw new RuntimeException('File not found');
        }

        if ($this->link->multi_query(file_get_contents($file_path))) {
            do {
                if ($result = $this->link->store_result()) {
                    $result->free();
                }

                if (!$this->link->more_results()) {
                    break;
                }
            } while ($this->link->next_result());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function databaseExists($database_name)
    {
        return $this->executeFirstCell('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', $database_name) == $database_name;
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database_name)
    {
        $this->execute('DROP DATABASE IF EXISTS ' . $this->escapeTableName($database_name));
    }

    /**
     * {@inheritdoc}
     */
    public function userExists($user_name)
    {
        return (bool) $this->executeFirstCell("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = ?) AS 'is_present'", $user_name);
    }

    /**
     * {@inheritdoc}
     */
    public function dropUser($user_name, $hostname = '%')
    {
        if ($this->userExists($user_name)) {
            $this->execute('DROP USER ?@?', $user_name, $hostname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTableNames($database_name = '')
    {
        if ($database_name) {
            $tables = $this->executeFirstColumn('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME', $database_name);
        } elseif ($this->database_name) {
            $tables = $this->executeFirstColumn('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME', $this->database_name);
        } else {
            $tables = $this->executeFirstColumn('SHOW TABLES');
        }

        if (empty($tables)) {
            $tables = [];
        }

        return $tables;
    }

    /**
     * {@inheritdoc}
     */
    public function tableExists($table_name)
    {
        return in_array($table_name, $this->getTableNames());
    }

    /**
     * {@inheritdoc}
     */
    public function dropTable($table_name)
    {
        $this->execute('DROP TABLE IF EXISTS ' . $this->escapeTableName($table_name));
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldNames($table_name)
    {
        $result = [];

        if ($rows = $this->execute("DESCRIBE {$this->escapeTableName($table_name)}")) {
            foreach ($rows as $row) {
                $result[] = $row['Field'];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fieldExists($table_name, $field_name)
    {
        return in_array($field_name, $this->getFieldNames($table_name)); // @TODO may be a better way to do this
    }

    /**
     * {@inheritdoc}
     */
    public function dropField($table_name, $field_name, $check_if_exists = true)
    {
        if ($check_if_exists && !$this->fieldExists($table_name, $field_name)) {
            return;
        }

        $this->execute("ALTER TABLE {$this->escapeTableName($table_name)} DROP {$this->escapeFieldName($field_name)}");
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexNames($table_name)
    {
        $result = [];

        if ($rows = $this->execute("SHOW INDEXES FROM {$this->escapeTableName($table_name)}")) {
            foreach ($rows as $row) {
                $key_name = $row['Key_name'];

                if (!in_array($key_name, $result)) {
                    $result[] = $key_name;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function indexExists($table_name, $index_name)
    {
        return in_array($index_name, $this->getIndexNames($table_name)); // @TODO may be a better way to do this
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndex($table_name, $index_name, $check_if_exists = true)
    {
        if ($check_if_exists && !$this->indexExists($table_name, $index_name)) {
            return;
        }

        $this->execute("ALTER TABLE {$this->escapeTableName($table_name)} DROP INDEX {$this->escapeFieldName($index_name)}");
    }

    /**
     * {@inheritdoc}
     */
    public function areForeignKeyChecksOn()
    {
        if ($row = $this->executeFirstRow("SHOW VARIABLES LIKE 'FOREIGN_KEY_CHECKS'")) {
            return in_array(strtolower($row['Value']), ['on', '1']);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function turnOnForeignKeyChecks()
    {
        $this->execute('SET foreign_key_checks = 1;');
    }

    /**
     * {@inheritdoc}
     */
    public function turnOffForeignKeyChecks()
    {
        $this->execute('SET foreign_key_checks = 0;');
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeyNames($table_name)
    {
        $result = [];

        if ($rows = $this->execute('SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = ? AND CONSTRAINT_NAME != ?', $table_name, 'PRIMARY')) {
            foreach ($rows as $row) {
                $result[] = $row['CONSTRAINT_NAME'];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function foreignKeyExists($table_name, $fk_name)
    {
        return in_array($fk_name, $this->getForeignKeyNames($table_name));
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey($table_name, $fk_name, $check_if_exists = true)
    {
        if ($check_if_exists && !$this->foreignKeyExists($table_name, $fk_name)) {
            return;
        }

        $this->execute("ALTER TABLE {$this->escapeTableName($table_name)} DROP FOREIGN KEY {$this->escapeFieldName($fk_name)}");
    }

    /**
     * Prepare (if needed) and execute SQL query.
     *
     * @param  string             $sql
     * @param  array|null         $arguments
     * @return mysqli_result|bool
     */
    private function prepareAndExecuteQuery($sql, $arguments)
    {
        if ($this->log || $this->on_log_query) {
            $microtime = microtime(true);

            $prepared_sql = empty($arguments) ?
                $sql :
                call_user_func_array([&$this, 'prepare'], array_merge([$sql], $arguments));

            $result = $this->link->query($prepared_sql);

            $execution_time = rtrim(number_format(microtime(true) - $microtime, 6, '.', ''), '0');

            if ($this->log) {
                if ($result === false) {
                    $this->log->error('Query error {error_message}', [
                        'error_message' => $this->link->error,
                        'error_code' => $this->link->errno,
                        'sql' => $prepared_sql,
                        'exec_time' => $execution_time,
                    ]);
                } else {
                    $this->log->debug('Query {sql} executed in {exec_time}s', [
                        'sql' => $prepared_sql,
                        'exec_time' => $execution_time,
                    ]);
                }
            }

            if ($this->on_log_query) {
                call_user_func($this->on_log_query, $prepared_sql, $execution_time);
            }

            return $result;
        } else {
            return empty($arguments) ?
                $this->link->query($sql) :
                $this->link->query(call_user_func_array([&$this, 'prepare'], array_merge([$sql], $arguments)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($sql, ...$arguments)
    {
        if (empty($arguments)) {
            return $sql;
        } else {
            $offset = 0;

            foreach ($arguments as $argument) {
                $question_mark_pos = mb_strpos($sql, '?', $offset);

                if ($question_mark_pos !== false) {
                    $escaped = $this->escapeValue($argument);
                    $escaped_len = mb_strlen($escaped);

                    $sql = mb_substr($sql, 0, $question_mark_pos) . $escaped . mb_substr($sql, $question_mark_pos + 1, mb_strlen($sql));

                    $offset = $question_mark_pos + $escaped_len;
                }
            }

            return $sql;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareConditions($conditions)
    {
        if ($conditions === null || is_string($conditions)) {
            return $conditions;
        } elseif (is_array($conditions)) {
            switch (count($conditions)) {
                case 0:
                    throw new InvalidArgumentException("Conditions can't be an empty array");
                case 1:
                    return array_shift($conditions);
                default:
                    return  call_user_func_array([&$this, 'prepare'], $conditions);
            }
        } else {
            throw new InvalidArgumentException('Invalid conditions argument value');
        }
    }

    /**
     * Try to recover from failed query.
     *
     * @param string     $sql
     * @param array|null $arguments
     * @param $load_mode
     * @param $return_mode
     * @return array|bool|DateTime|float|int|mixed|null|string|void
     * @throws QueryException
     */
    private function tryToRecoverFromFailedQuery($sql, $arguments, $load_mode, $return_mode)
    {
        switch ($this->link->errno) {

            // Non-transactional tables not rolled back!
            case 1196:
                return null;

            // Server gone away
            case 2006:
            case 2013:
                return $this->handleMySqlGoneAway($sql, $arguments, $load_mode, $return_mode);

            // Deadlock detection and retry
            case 1213:
                return $this->handleDeadlock();

            // Other error
            default:
                throw new QueryException($this->link->error, $this->link->errno);
        }
    }

    /**
     * @param $sql
     * @param $arguments
     * @param $load_mode
     * @param $return_mode
     * @return array|bool|DateTime|float|int|mixed|null|string
     * @throws QueryException
     */
    private function handleMySqlGoneAway($sql, $arguments, $load_mode, $return_mode)
    {
        if (!$this->link->ping()) {
            $this->log->notice('Mysql reconnect failed');

            throw new QueryException($this->link->error, $this->link->errno);
        }

        return $this->advancedExecute($sql, $arguments, $load_mode, $return_mode);
    }

    private function handleDeadlock()
    {
        throw new RuntimeException('Not implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function escapeValue($unescaped)
    {
        // Date value
        if ($unescaped instanceof DateValue) {
            return "'" . $this->link->real_escape_string($unescaped->format('Y-m-d')) . "'";

        // Date time value (including DateTimeValue)
        } elseif ($unescaped instanceof DateTime) {
            return "'" . $this->link->real_escape_string($unescaped->format('Y-m-d H:i:s')) . "'";

        // Float
        } else {
            if (is_float($unescaped)) {
                return "'" . str_replace(',', '.', (float) $unescaped) . "'"; // replace , with . for locales where comma is used by the system (German for example)

            // Boolean (maps to TINYINT(1))
            } else {
                if (is_bool($unescaped)) {
                    return $unescaped ? "'1'" : "'0'";

                // NULL
                } else {
                    if ($unescaped === null) {
                        return 'NULL';

                    // Escape first cell of each row
                    } else {
                        if ($unescaped instanceof ResultInterface) {
                            if ($unescaped->count() < 1) {
                                throw new InvalidArgumentException("Empty results can't be escaped");
                            }

                            $escaped = [];

                            foreach ($unescaped as $v) {
                                $escaped[] = $this->escapeValue(array_shift($v));
                            }

                            return '(' . implode(',', $escaped) . ')';

                        // Escape each array element
                        } else {
                            if (is_array($unescaped)) {
                                if (empty($unescaped)) {
                                    throw new InvalidArgumentException("Empty arrays can't be escaped");
                                }

                                $escaped = [];

                                foreach ($unescaped as $v) {
                                    $escaped[] = $this->escapeValue($v);
                                }

                                return '(' . implode(',', $escaped) . ')';

                            // Regular string and integer escape
                            } else {
                                if (is_scalar($unescaped)) {
                                    return "'" . $this->link->real_escape_string($unescaped) . "'";
                                } else {
                                    throw new InvalidArgumentException('Value is expected to be scalar, array, or instance of: DateTime or Result');
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function escapeFieldName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * {@inheritdoc}
     */
    public function escapeTableName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * {@inheritdoc}
     */
    public function escapeDatabaseName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * @var ValueCasterInterface
     */
    private $default_caster;

    /**
     * @return ValueCasterInterface
     */
    private function &getDefaultCaster()
    {
        if (empty($this->default_caster)) {
            $this->default_caster = new ValueCaster();
        }

        return $this->default_caster;
    }

    // ---------------------------------------------------
    //  Events
    // ---------------------------------------------------

    /**
     * @var callable|null
     */
    private $on_log_query;

    /**
     * Set a callback that will receive every query after we run it.
     *
     * Callback should accept two parameters: first for SQL that was ran, and second for time that it took to run
     *
     * @param callable|null $callback
     */
    public function onLogQuery(callable $callback = null)
    {
        if ($callback === null || is_callable($callback)) {
            $this->on_log_query = $callback;
        } else {
            throw new InvalidArgumentException('Callback needs to be NULL or callable');
        }
    }
}
