<?php

namespace ActiveCollab\DatabaseConnection;

use ActiveCollab\DatabaseConnection\Exception\Query;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseConnection\Result\Result;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseConnection\BatchInsert\BatchInsert;
use ActiveCollab\DatabaseConnection\BatchInsert\BatchInsertInterface;
use ActiveCollab\DateValue\DateValue;
use Closure;
use DateTime;
use Exception;
use InvalidArgumentException;
use mysqli;
use mysqli_result;
use Psr\Log\LoggerInterface;

/**
 * @package ActiveCollab\DatabaseConnection
 */
class Connection implements ConnectionInterface
{
    /**
     * @var mysqli
     */
    private $link;

    /**
     * @var LoggerInterface
     */
    private $log;

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
     * Execute a query and return a result
     *
     * @param  string                    $sql
     * @param  mixed                     ...$arguments
     * @return ResultInterface|true|null
     */
    public function execute($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_ALL_ROWS);
    }

    /**
     * Return first row that provided SQL query returns
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return array
     */
    public function executeFirstRow($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_FIRST_ROW);
    }

    /**
     * Return value from the first cell of each column that provided SQL query returns
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return array
     */
    public function executeFirstColumn($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_FIRST_COLUMN);
    }

    /**
     * Return value from the first cell of the first row that provided SQL query returns
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public function executeFirstCell($sql, ...$arguments)
    {
        return $this->advancedExecute($sql, $arguments, ConnectionInterface::LOAD_FIRST_CELL);
    }

    /**
     * Prepare and execute query, while letting the developer change the load and return modes
     *
     * @param  string     $sql
     * @param  mixed      $arguments
     * @param  int        $load_mode
     * @param  int        $return_mode
     * @param  string     $return_class_or_field
     * @param  array|null $constructor_arguments
     * @return mixed
     * @throws Query
     */
    public function advancedExecute($sql, $arguments = null, $load_mode = ConnectionInterface::LOAD_ALL_ROWS, $return_mode = ConnectionInterface::RETURN_ARRAY, $return_class_or_field = null, array $constructor_arguments = null)
    {
        if ($return_mode == ConnectionInterface::RETURN_OBJECT_BY_CLASS && empty($return_class_or_field)) {
            throw new InvalidArgumentException('Class is required');
        } elseif ($return_mode == ConnectionInterface::RETURN_OBJECT_BY_FIELD && empty($return_class_or_field)) {
            throw new InvalidArgumentException('Field name is required');
        }

        $query_result = $this->prepareAndExecuteQuery($sql, $arguments);

        if ($query_result === false) {
            $query_result = $this->tryToRecoverFromFailedQuery($sql, $arguments);
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
                        return new Result($query_result, $return_mode, $return_class_or_field, $constructor_arguments); // Don't close result, we need it
                }
            } else {
                $result = null;
            }

            $query_result->close();

            return $result;
        } elseif ($query_result === true) {
            return true;
        } else {
            throw new Query($this->link->error, $this->link->errno);
        }
    }

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

        $count = $field == '*' ?  'COUNT(*)' :  'COUNT(' . $this->escapeFieldName($field) . ')';

        return $this->executeFirstCell("SELECT $count AS 'row_count' FROM " . $this->escapeTableName($table_name) . $where);
    }

    /**
     * Insert into $table a row that is reperesented with $values (key is field name, and value is value that we need to set)
     *
     * @param  string $table
     * @param  array  $field_value_map
     * @param  string $mode
     * @return int
     * @throws InvalidArgumentException
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
     * Prepare a batch insert utility instance
     *
     * @param  string               $table_name
     * @param  array                $fields
     * @param  int                  $rows_per_batch
     * @param  string               $mode
     * @return BatchInsertInterface
     */
    public function batchInsert($table_name, array $fields, $rows_per_batch = 50, $mode = self::INSERT)
    {
        return new BatchInsert($this, $table_name, $fields, $rows_per_batch, $mode);
    }

    /**
     * Return last insert ID
     *
     * @return integer
     */
    public function lastInsertId()
    {
        return $this->link->insert_id;
    }

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
     * Delete one or more records from the table
     *
     * $conditions can be a string, or an array where first element is a patter and other elements are arguments
     *
     * @param  string            $table_name
     * @param  string|array|null $conditions
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($table_name, $conditions = null)
    {
        if ($conditions = $this->prepareConditions($conditions)) {
            $conditions = " WHERE $conditions";
        }

        $this->execute('DELETE FROM ' . $this->escapeTableName($table_name) . $conditions);

        return $this->affectedRows();
    }

    /**
     * Return number of affected rows
     *
     * @return integer
     */
    public function affectedRows()
    {
        return $this->link->affected_rows;
    }

    /**
     * Run body commands within a transation
     *
     * @param  Closure      $body
     * @param  Closure|null $on_success
     * @param  CLosure|null $on_error
     * @throws Exception
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
     * Transaction level
     *
     * @var integer
     */
    private $transaction_level = 0;

    /**
     * Begin transaction
     */
    public function beginWork()
    {
        if ($this->transaction_level == 0) {
            $this->execute('BEGIN WORK');
        }
        $this->transaction_level++;
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        if ($this->transaction_level) {
            $this->transaction_level--;
            if ($this->transaction_level == 0) {
                $this->execute('COMMIT');
            }
        }
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        if ($this->transaction_level) {
            $this->transaction_level = 0;
            $this->execute('ROLLBACK');
        }
    }

    /**
     * Return true if system is in transaction
     *
     * @return boolean
     */
    public function inTransaction()
    {
        return $this->transaction_level > 0;
    }

    /**
     * Return true if table named $table_name exists in the selected database
     *
     * @param  string $table_name
     * @return bool
     */
    public function tableExists($table_name)
    {
        return in_array($table_name, $this->getTableNames());
    }

    /**
     * Return array of table names
     *
     * @return array
     */
    public function getTableNames()
    {
        $tables = $this->executeFirstColumn('SHOW TABLES');

        if (empty($tables)) {
            $tables = [];
        }

        return $tables;
    }

    /**
     * Drop a table named $table_name from selected database
     *
     * @param string $table_name
     */
    public function dropTable($table_name)
    {
        $this->execute('DROP TABLE ' . $this->escapeTableName($table_name));
    }

    /**
     * Prepare (if needed) and execute SQL query
     *
     * @param  string     $sql
     * @param  array|null $arguments
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

            $execution_time = microtime(true) - $microtime;

            if ($this->log) {
                if ($result === false) {
                    $this->log->error('SQL query error' . $this->link->error, [
                        'error_message' => $this->link->error,
                        'error_code' => $this->link->errno,
                        'sql' => $prepared_sql,
                        'exec_time' => $execution_time,
                    ]);
                } else {
                    $this->log->debug('SQL query executed', [
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
     * Prepare SQL (replace ? with data from $arguments array)
     *
     * @param  string $sql
     * @param  mixed  ...$arguments
     * @return string
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
     * Prepare conditions and return them as string
     *
     * @param  array|string|null $conditions
     * @return string
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
     * Try to recover from failed query
     *
     * @param  string     $sql
     * @param  array|null $arguments
     * @return null
     * @throws Query
     */
    private function tryToRecoverFromFailedQuery($sql, $arguments)
    {
        switch ($this->link->errno) {

            // Non-transactional tables not rolled back!
            case 1196:
                return null;

            // Server gone away
            case 2006:
            case 2013:
                return $this->handleMySqlGoneAway($sql, $arguments);

            // Deadlock detection and retry
            case 1213:
                return $this->handleDeadlock($sql, $arguments);

            // Other error
            default:
                throw new Query($this->link->error, $this->link->errno);
        }
    }

    /**
     * Escape string before we use it in query...
     *
     * @param  mixed $unescaped
     * @return string
     * @throws InvalidArgumentException
     */
    public function escapeValue($unescaped)
    {
        // Date value
        if ($unescaped instanceof DateValue) {
            return "'" . $this->link->real_escape_string($unescaped->format("Y-m-d")) . "'";

        // Date time value (including DateTimeValue)
        } elseif ($unescaped instanceof DateTime) {
            return "'" . $this->link->real_escape_string($unescaped->format('Y-m-d H:i:s')) . "'";

        // Float
        } else {
            if (is_float($unescaped)) {
                return "'" . str_replace(',', '.', (float)$unescaped) . "'"; // replace , with . for locales where comma is used by the system (German for example)

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
     * Escape table field name
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeFieldName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * Escape table name
     *
     * @param  string $unescaped
     * @return string
     */
    public function escapeTableName($unescaped)
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
     * Set a callback that will receive every query after we run it
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
