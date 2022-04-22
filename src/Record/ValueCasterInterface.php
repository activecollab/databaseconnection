<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Record;

interface ValueCasterInterface
{
    const CAST_INT = 'int';
    const CAST_FLOAT = 'float';
    const CAST_STRING = 'string';
    const CAST_BOOL = 'bool';
    const CAST_DATE = 'date';
    const CAST_DATETIME = 'datetime';
    const CAST_JSON = 'json';

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
     */
    public function getTypeByFieldName(string $field_name): string;
}
