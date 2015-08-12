<?php

  namespace ActiveCollab\DatabaseConnection;

  use ActiveCollab\DatabaseConnection\Exception\Query;
  use mysqli;
  use mysqli_result;
  use DateTime;
  use InvalidArgumentException;
  use BadMethodCallException;

  /**
   * @package ActiveCollab\DatabaseConnection
   */
  class Connection
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
     * @var mysqli
     */
    private $link;

    /**
     * @param mysqli $link
     */
    public function __construct(mysqli &$link)
    {
      $this->link = $link;
    }


    public function execute()
    {
      return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_ALL_ROWS);
    }

    public function executeFirstRow()
    {
      return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_FIRST_ROW);
    }

    public function executeFirstColumn()
    {
      return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_FIRST_COLUMN);
    }

    public function executeFirstCell()
    {
      return $this->executeBasedOnFunctionArguments(func_get_args(), self::LOAD_FIRST_CELL);
    }

    private function executeBasedOnFunctionArguments($arguments, $load_mode)
    {
      if (empty($arguments)) {
        throw new BadMethodCallException('SQL query with optional list of arguments was expected');
      } else {
        return $this->execute2(array_shift($arguments), $arguments, $load_mode);
      }
    }

    /**
     * Execute SQL query
     *
     * @param  string                                       $sql
     * @param  mixed                                        $arguments
     * @param  int                                          $load
     * @param  int                                          $return_mode
     * @param  string                                       $return_class_or_field
     * @return array|bool|DBResult|mixed|MySQLDBResult|null
     * @throws Query
     * @throws DBQueryError
     * @throws DBNotConnectedError
     * @throws Exception
     */
    public function execute2($sql, $arguments = null, $load = self::LOAD_ALL_ROWS, $return_mode = self::RETURN_ARRAY, $return_class_or_field = null)
    {
      $query_result = $this->prepareAndExecuteQuery($sql, $arguments);

      if ($query_result === false) {
        $query_result = $this->tryToRecoverFromFailedQuery($sql, $arguments);
      }

      if ($query_result instanceof mysqli_result) {
        if ($query_result->num_rows > 0) {
          switch ($load) {
            case self::LOAD_FIRST_ROW:
              $result = self::rowToResult($query_result->fetch_assoc(), $return_mode, $return_class_or_field); break;

            case self::LOAD_FIRST_COLUMN:
              $result = [];

              if ($query_result->num_rows > 0) {
                $cast = null;

                while ($row = $query_result->fetch_assoc()) {
                  foreach ($row as $k => $v) {
                    if (empty($cast)) {
                      if ($k == 'id' || str_ends_with($k, '_id')) {
                        $cast = DBResult::CAST_INT;
                      } elseif (str_starts_with($k, 'is_')) {
                        $cast = DBResult::CAST_BOOL;
                      } else {
                        $cast = DBResult::CAST_STRING;
                      }
                    }

                    if ($cast == DBResult::CAST_INT) {
                      $result[] = (integer) $v;
                    } elseif ($cast == DBResult::CAST_BOOL) {
                      $result[] = (boolean) $v;
                    } else {
                      $result[] = $v;
                    }

                    break;
                  }
                  //$result[] = array_shift($row);
                }
              }

              break;

            case self::LOAD_FIRST_CELL:
              $result = null;

              foreach ($query_result->fetch_assoc() as $k => $v) {
                if ($k == 'id' || $k == 'row_count' || str_ends_with($k, '_id')) {
                  $result = (integer) $v;
                } elseif (str_starts_with($k, 'is_')) {
                  $result = (boolean) $v;
                } else {
                  $result = $v;
                }

                break;
              }

              break;
            default:
              return new MySQLDBResult($query_result, $return_mode, $return_class_or_field); // Don't close result, we need it
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
     * Prepare (if needed) and execute SQL query
     *
     * @param  string             $sql
     * @param  array|null         $arguments
     * @return mysqli_result|bool
     */
    private function prepareAndExecuteQuery($sql, $arguments)
    {
//      if (empty($this->link)) {
//        throw new DBNotConnectedError();
//      }

      if ($this->on_log_query) {
        $microtime = microtime(true);

        $prepared_sql = $this->prepare($sql, $arguments);
        $result = $this->link->query($prepared_sql);

        call_user_func($this->on_log_query, $prepared_sql, microtime(true) - $microtime);

        return $result;
      } else {
        return $this->link->query($this->prepare($sql, $arguments));
      }
    }

    /**
     * Prepare SQL (replace ? with data from $arguments array)
     *
     * @param  string $sql
     * @param  array  $arguments
     * @return string
     */
    public function prepare($sql, $arguments = null)
    {
      if (!empty($arguments) && is_array($arguments)) {
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
      }

      return $sql;
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
          return $this->handleMySqlGoneAway($sql, $arguments); break;

        // Deadlock detection and retry
        case 1213:
          return $this->handleDeadlock($sql, $arguments); break;

        // Other error
        default:
          throw new Query($this->link->error, $this->link->errno);
      }
    }

    /**
     * Escape string before we use it in query...
     *
     * @param  mixed                    $unescaped
     * @return string
     * @throws InvalidArgumentException
     */
    public function escapeValue($unescaped)
    {
      // Date time value
      if ($unescaped instanceof DateTime) {
        return "'" . $this->link->real_escape_string(date('Y-m-d H:i:s', $unescaped->getTimestamp())) . "'";

      // Float
      } else if (is_float($unescaped)) {
        return "'" . str_replace(',', '.', (float) $unescaped) . "'"; // replace , with . for locales where comma is used by the system (German for example)

      // Boolean (maps to TINYINT(1))
      } else if (is_bool($unescaped)) {
        return $unescaped ? "'1'" : "'0'";

        // NULL
      } else if ($unescaped === null) {
        return 'NULL';

      // Escape first cell of each row
      } else if ($unescaped instanceof Result) {
        if ($unescaped->count() < 1) {
          throw new InvalidArgumentException("Empty results can't be escaped");
        }

        $escaped = [];

        foreach ($unescaped as $v) {
          $escaped[] = $this->escapeValue(array_shift($v));
        }

        return '(' . implode(',', $escaped) . ')';

      // Escape each array element
      } else if (is_array($unescaped)) {
        if (empty($unescaped)) {
          throw new InvalidArgumentException("Empty arrays can't be escaped");
        }

        $escaped = [];

        foreach ($unescaped as $v) {
          $escaped[] = $this->escapeValue($v);
        }

        return '(' . implode(',', $escaped) . ')';

      // Regular string and integer escape
      } else if (is_scalar($unescaped)) {
        return "'" . $this->link->real_escape_string($unescaped) . "'";
      } else {
        throw new InvalidArgumentException('Value is expected to be scalar, array, or instance of: DateTime or Result');
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