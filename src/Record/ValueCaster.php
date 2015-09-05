<?php

  namespace ActiveCollab\DatabaseConnection\Record;

  use DateTime;
  use DateTimeZone;

  /**
   * @package ActiveCollab\DatabaseConnection\Record
   */
  class ValueCaster
  {
    const CAST_INT = 'int';
    const CAST_FLOAT = 'float';
    const CAST_STRING = 'string';
    const CAST_BOOL = 'bool';
    const CAST_DATE = 'date';
    const CAST_DATETIME = 'datetime';

    /**
     * @var array
     */
    private $dictated = [ 'id' => self::CAST_INT, 'row_count' => self::CAST_INT ];

    /**
     * @param array|null $dictated
     */
    public function __construct(array $dictated = null)
    {
      if ($dictated && is_array($dictated)) {
        $this->dictated = array_merge($this->dictated, $dictated);
      }
    }

    /**
     * Cast row value to native PHP types based on caster settings
     *
     * @param array $row
     */
    public function castRowValues(array &$row)
    {
      foreach ($row as $field_name => $value) {
        $row[$field_name] = $this->castValue($field_name, $value);
      }
    }

    /**
     * Cast a single value
     *
     * @param  string                                    $field_name
     * @param  mixed                                     $value
     * @return bool|DateTime|float|int|mixed|null|string
     */
    public function castValue($field_name, $value)
    {
      if ($value === null) {
        return null; // NULL remains NULL
      } else {
        switch ($this->getTypeByFieldName($field_name)) {
          case self::CAST_INT:
            return (int) $value;
          case self::CAST_FLOAT:
            return (float) $value;
          case self::CAST_STRING:
            return (string) $value;
          case self::CAST_BOOL:
            return (bool) $value;
          case self::CAST_DATE:
          case self::CAST_DATETIME:
            return new DateTime($value, new DateTimeZone('UTC'));
          default:
            return (string) $value;
        }
      }
    }

    /**
     * Return type by field name
     *
     * @param  string $field_name
     * @return string
     */
    public function getTypeByFieldName($field_name)
    {
      if (isset($this->dictated[$field_name])) {
        return $this->dictated[$field_name];
      }

      if (substr($field_name, 0, 3) == 'is_') {
        return self::CAST_BOOL;
      } else {
        $last_three = substr($field_name, strlen($field_name) - 3);

        if ($last_three == '_id') {
          return self::CAST_INT;
        } else if ($last_three == '_on') {
          return self::CAST_DATE;
        } else if ($last_three == '_at') {
          return self::CAST_DATETIME;
        }
      }

      return self::CAST_STRING;
    }
  }
