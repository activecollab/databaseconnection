<?php

  namespace ActiveCollab\DatabaseConnection;

  use mysqli;
  use DateTime;
  use InvalidArgumentException;

  /**
   * @package ActiveCollab\DatabaseConnection
   */
  class Connection
  {
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

    /**
     * Escape string before we use it in query...
     *
     * @param  mixed                    $unescaped
     * @return string
     * @throws InvalidArgumentException
     */
    public function escape($unescaped)
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
          $escaped[] = $this->escape(array_shift($v));
        }

        return '(' . implode(',', $escaped) . ')';

      // Escape each array element
      } else if (is_array($unescaped)) {
        if (empty($unescaped)) {
          throw new InvalidArgumentException("Empty arrays can't be escaped");
        }

        $escaped = [];

        foreach ($unescaped as $v) {
          $escaped[] = $this->escape($v);
        }

        return '(' . implode(',', $escaped) . ')';

      // Regular string and integer escape
      } else if (is_scalar($unescaped)) {
        return "'" . $this->link->real_escape_string($unescaped) . "'";
      } else {
        throw new InvalidArgumentException('Value is expected to be scalar, array, or instance of: DateTime or Result');
      }
    }
  }