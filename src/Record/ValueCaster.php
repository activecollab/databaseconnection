<?php

namespace ActiveCollab\DatabaseConnection\Record;

use ActiveCollab\DateValue\DateValue;
use ActiveCollab\DateValue\DateTimeValue;

/**
 * @package ActiveCollab\DatabaseConnection\Record
 */
class ValueCaster implements ValueCasterInterface
{
    /**
     * @var array
     */
    private $dictated = ['id' => self::CAST_INT, 'row_count' => self::CAST_INT];

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
     * Cast row value to native PHP types based on caster settings.
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
     * Cast a single value.
     *
     * @param  string $field_name
     * @param  mixed  $value
     *
     * @return mixed
     */
    public function castValue($field_name, $value)
    {
        if ($value === null) {
            return null; // NULL remains NULL
        }

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
                return new DateValue($value, 'UTC');
            case self::CAST_DATETIME:
                return new DateTimeValue($value, 'UTC');
            default:
                return (string) $value;
        }
    }

    /**
     * Return type by field name.
     *
     * @param string $field_name
     *
     * @return string
     */
    public function getTypeByFieldName($field_name)
    {
        if (isset($this->dictated[$field_name])) {
            return $this->dictated[$field_name];
        }

        if (substr($field_name, 0, 3) === 'is_') {
            return self::CAST_BOOL;
        }

        $last_three = substr($field_name, -3);

        if ($last_three === '_id') {
            return self::CAST_INT;
        }

        if ($last_three === '_on') {
            return self::CAST_DATE;
        }

        if ($last_three === '_at') {
            return self::CAST_DATETIME;
        }

        return self::CAST_STRING;
    }
}
