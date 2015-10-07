<?php

namespace ActiveCollab\DatabaseConnection\Record;

/**
 * @package ActiveCollab\DatabaseConnection\Record
 */
interface ValueCasterInterface
{
    const CAST_INT = 'int';
    const CAST_FLOAT = 'float';
    const CAST_STRING = 'string';
    const CAST_BOOL = 'bool';
    const CAST_DATE = 'date';
    const CAST_DATETIME = 'datetime';

    /**
     * Cast row value to native PHP types based on caster settings.
     *
     * @param array $row
     */
    public function castRowValues(array &$row);

    /**
     * Cast a single value.
     *
     * @param string $field_name
     * @param mixed  $value
     *
     * @return bool|\DateTime|float|int|mixed|null|string
     */
    public function castValue($field_name, $value);

    /**
     * Return type by field name.
     *
     * @param string $field_name
     *
     * @return string
     */
    public function getTypeByFieldName($field_name);
}
