<?php

namespace ActiveCollab\DatabaseConnection;

use ActiveCollab\DatabaseConnection\Exception\Query;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Result\Result;
use BadMethodCallException;
use Closure;
use DateTime;
use Exception;
use InvalidArgumentException;
use mysqli;
use mysqli_result;

class Connection
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
     * @var mysqli
     */
    private $link;

    /**
     * @var callable|null
     */
    private $on_log_query;

    /**
     * Transaction level.
     *
     * @var int
     */
    private $transaction_level = 0;

    /**
     * @var ValueCaster
     */
    private $default_caster;

    /**
     * @param mysqli $link
     */
    public function __construct(mysqli $link)
    {
        $this->link = $link;
    }

    /**
     * Execute a query and return a result.
     *
     * @return Result|true|null
     */
    public function execute()
    {
        return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_ALL_ROWS);
    }

    /**
     * Return first row that provided SQL query returns.
     *
     * @return array
     */
    public function executeFirstRow()
    {
        return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_FIRST_ROW);
    }

    /**
     * Return value from the first cell of each column that provided SQL query returns.
     *
     * @return array
     */
    public function executeFirstColumn()
    {
        return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_FIRST_COLUMN);
    }

    /**
     * Return value from the first cell of the first row that provided SQL query returns.
     *
     * @return mixed
     */
    public function executeFirstCell()
    {
        return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_FIRST_CELL);
    }

    /**
     * Prepare and execute query, while letting the developer change the load and return modes.
     *
     * @param string $sql
     * @param mixed  $arguments
     * @param int    $load_mode
     * @param int    $return_mode
     * @param string $return_class_or_field
     *
     * @return mixed
     *
     * @throws Query
     */
    public function advancedExecute($sql, $arguments = null, $load_mode = self::LOAD_ALL_ROWS, $return_mode = self::RETURN_ARRAY, $return_class_or_field = null)
    {
        $query_result = $this->prepareAndExecuteQuery($sql, $arguments);

        if ($query_result === false) {
            $query_result = $this->tryToRecoverFromFailedQuery($sql, $arguments);
        }

        if ($query_result === true) {
            return true;
        }

        if ($query_result instanceof mysqli_result) {
            if ($query_result->num_rows > 0) {
                switch ($load_mode) {
                    case self::LOAD_FIRST_ROW:
                        $result = $query_result->fetch_assoc();
                        $this->getDefaultCaster()->castRowValues($result);

                        break;
                    case self::LOAD_FIRST_COLUMN:
                        $result = [];

                        while ($row = $query_result->fetch_assoc()) {
                            foreach ($row as $k => $v) {
                                $result[] = $this->getDefaultCaster()->castValue($k, $v);
                                break; // Done after first cell in a row
                            }
                        }

                        break;
                    case self::LOAD_FIRST_CELL:
                        $result = null;

                        foreach ($query_result->fetch_assoc() as $k => $v) {
                            $result = $this->getDefaultCaster()->castValue($k, $v);
                            break; // Done after first cell
                        }

                        break;
                    default:
                        // Don't close result, we need it
                        return new Result($query_result, $return_mode, $return_class_or_field);
                }
            } else {
                $result = null;
            }

            $query_result->close();

            return $result;
        }

        throw new Query($this->link->error, $this->link->errno);
    }

    /**
     * Return number of affected rows.
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->link->affected_rows;
    }

    /**
     * Return last insert ID.
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->link->insert_id;
    }

    /**
     * Run body commands within a transation.
     *
     * @param Closure      $body
     * @param Closure|null $on_success
     * @param CLosure|null $on_error
     *
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
     * Begin transaction.
     */
    public function beginWork()
    {
        if ($this->transaction_level == 0) {
            $this->execute('BEGIN WORK');
        }
        ++$this->transaction_level;
    }

    /**
     * Commit transaction.
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
     * Rollback transaction.
     */
    public function rollback()
    {
        if ($this->transaction_level) {
            $this->transaction_level = 0;
            $this->execute('ROLLBACK');
        }
    }

    /**
     * Return true if system is in transaction.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->transaction_level > 0;
    }

    /**
     * Prepare SQL (replace ? with data from $arguments array).
     *
     * @return string
     */
    public function prepare()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            throw new InvalidArgumentException('Pattern expected');
        }

        if (count($arguments) === 1) {
            return $arguments[0];
        }

        $sql = array_shift($arguments);
        $offset = 0;

        foreach ($arguments as $argument) {
            $question_mark_pos = mb_strpos($sql, '?', $offset);

            if ($question_mark_pos !== false) {
                $escaped = $this->escapeValue($argument);
                $escaped_len = mb_strlen($escaped);

                $sql = mb_substr($sql, 0, $question_mark_pos).$escaped.mb_substr($sql, $question_mark_pos + 1, mb_strlen($sql));

                $offset = $question_mark_pos + $escaped_len;
            }
        }

        return $sql;
    }

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

    /**
     * Escape string before we use it in query...
     *
     * @param mixed $unescaped
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function escapeValue($unescaped)
    {
        // Date time value
        if ($unescaped instanceof DateTime) {
            return "'".$this->link->real_escape_string(date('Y-m-d H:i:s', $unescaped->getTimestamp()))."'";
        }

        // Float
        if (is_float($unescaped)) {
            // replace , with . for locales where comma is used by the system (German for example)
            return "'".str_replace(',', '.', (float) $unescaped)."'";
        }

        // Boolean (maps to TINYINT(1))
        if (is_bool($unescaped)) {
            return $unescaped ? "'1'" : "'0'";
        }

        // NULL
        if ($unescaped === null) {
            return 'NULL';
        }

        // Escape first cell of each row
        if ($unescaped instanceof Result) {
            if ($unescaped->count() < 1) {
                throw new InvalidArgumentException("Empty results can't be escaped");
            }

            $escaped = [];

            foreach ($unescaped as $v) {
                $escaped[] = $this->escapeValue(array_shift($v));
            }

            return '('.implode(',', $escaped).')';
        }

        // Escape each array element
        if (is_array($unescaped)) {
            if (empty($unescaped)) {
                throw new InvalidArgumentException("Empty arrays can't be escaped");
            }

            $escaped = [];

            foreach ($unescaped as $v) {
                $escaped[] = $this->escapeValue($v);
            }

            return '('.implode(',', $escaped).')';
        }

        // Regular string and integer escape
        if (is_scalar($unescaped)) {
            return "'".$this->link->real_escape_string($unescaped)."'";
        }

        throw new InvalidArgumentException('Value is expected to be scalar, array, or instance of: DateTime or Result');
    }

    /**
     * Escape table field name.
     *
     * @param string $unescaped
     *
     * @return string
     */
    public function escapeFieldName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * Escape table name.
     *
     * @param string $unescaped
     *
     * @return string
     */
    public function escapeTableName($unescaped)
    {
        return "`$unescaped`";
    }

    /**
     * @return ValueCaster
     */
    private function getDefaultCaster()
    {
        if (empty($this->default_caster)) {
            $this->default_caster = new ValueCaster();
        }

        return $this->default_caster;
    }

    /**
     * Prepare (if needed) and execute SQL query.
     *
     * @param string     $sql
     * @param array|null $arguments
     *
     * @return mysqli_result|bool
     */
    private function prepareAndExecuteQuery($sql, $arguments)
    {
        if (!$this->on_log_query) {
            return $this->link->query($this->prepareQuery($sql, $arguments));
        }

        $microtime = microtime(true);

        $prepared_sql = $this->prepareQuery($sql, $arguments);

        $result = $this->link->query($prepared_sql);

        call_user_func($this->on_log_query, $prepared_sql, microtime(true) - $microtime);

        return $result;
    }

    /**
     * Prepare SQL query.
     *
     * @param string     $sql
     * @param array|null $arguments
     *
     * @return mysqli_stmt|string
     */
    private function prepareQuery($sql, $arguments)
    {
        if (empty($arguments)) {
            return $sql;
        }

        return call_user_func_array([$this, 'prepare'], array_merge([$sql], $arguments));
    }

    /**
     * Use arguments to prepare and execute a query, and return it in expected form.
     *
     * @param array  $arguments
     * @param string $load_mode
     *
     * @return mixed
     *
     * @throws Query
     */
    private function executeBasedOnFunctionArguments($arguments, $load_mode)
    {
        if (empty($arguments)) {
            throw new BadMethodCallException(
                'SQL query with optional list of arguments was expected'
            );
        }

        return $this->advancedExecute(array_shift($arguments), $arguments, $load_mode);
    }

    /**
     * Try to recover from failed query.
     *
     * @param string     $sql
     * @param array|null $arguments
     *
     * @throws Query
     */
    private function tryToRecoverFromFailedQuery($sql, $arguments)
    {
        switch ($this->link->errno) {
            // Non-transactional tables not rolled back!
            case 1196:
                //NO-OP ATM
                return;
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
     * @param string     $sql
     * @param array|null $arguments
     *
     * @throws BadMethodCallException
     */
    private function handleMySqlGoneAway($sql, $arguments)
    {
        throw new BadMethodCallException(
            sprintf('Method %s is not implemented.', __METHOD__)
        );
    }

    /**
     * @param string     $sql
     * @param array|null $arguments
     *
     * @throws BadMethodCallException
     */
    private function handleDeadlock($sql, $arguments)
    {
        throw new BadMethodCallException(
            printf('Method %s is not implemented.', __METHOD__)
        );
    }
}
