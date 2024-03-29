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

use ActiveCollab\DatabaseConnection\Spatial\WktParser\WktParser;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateValue;
use RuntimeException;

class ValueCaster implements ValueCasterInterface
{
    private array $dictated = [
        'id' => self::CAST_INT,
        'row_count' => self::CAST_INT,
    ];

    public function __construct(array $dictated = null)
    {
        if ($dictated) {
            $this->dictated = array_merge($this->dictated, $dictated);
        }
    }

    /**
     * Cast row value to native PHP types based on caster settings.
     */
    public function castRowValues(array &$row): void
    {
        foreach ($row as $field_name => $value) {
            $row[$field_name] = $this->castValue($field_name, $value);
        }
    }

    public function castValue(string $field_name, mixed $value): mixed
    {
        if ($value === null) {
            return null; // NULL remains NULL
        }

        switch ($this->getTypeByFieldName($field_name, $value)) {
            case self::CAST_INT:
                return (int) $value;
            case self::CAST_FLOAT:
                return (float) $value;
            case self::CAST_BOOL:
                return (bool) $value;
            case self::CAST_DATE:
                return new DateValue($value, 'UTC');
            case self::CAST_DATETIME:
                return new DateTimeValue($value, 'UTC');
            case self::CAST_JSON:
                if (empty($value)) {
                    return null;
                }

                $result = json_decode($value, true);

                if (empty($result) && json_last_error()) {
                    throw new RuntimeException(
                        sprintf('Failed to parse JSON. Reason: %s', json_last_error_msg()),
                        json_last_error()
                    );
                }

                return $result;
            case self::CAST_SPATIAL:
                return (new WktParser())->parse((string) $value);
        }

        return (string) $value;
    }

    public function getTypeByFieldName(string $field_name, mixed $value = null): string
    {
        if (isset($this->dictated[$field_name])) {
            return $this->dictated[$field_name];
        }

        if (str_starts_with($field_name, 'is_')
            || in_array(substr($field_name, 0, 4), ['has_', 'had_', 'was_'])
            || in_array(substr($field_name, 0, 5), ['were_', 'have_'])
        ) {
            return self::CAST_BOOL;
        }

        $last_three = substr($field_name, -3);

        if ($last_three === '_id' && ($value === null || ctype_digit($value))) {
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
