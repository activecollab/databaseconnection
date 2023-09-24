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
    const CAST_SPATIAL = 'wkt';

    /**
     * Cast row value to native PHP types based on caster settings.
     *
     * @param array $row
     */
    public function castRowValues(array &$row): void;

    /**
     * Cast a single value.
     */
    public function castValue(string $field_name, mixed $value): mixed;

    /**
     * Return type by field name.
     */
    public function getTypeByFieldName(string $field_name, mixed $value = null): string;
}
