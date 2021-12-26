<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Result;

use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * @package ActiveCollab\DatabaseConnection\Result
 */
interface ResultInterface extends IteratorAggregate, ArrayAccess, Countable, JsonSerializable
{
    /**
     * Return resource.
     *
     * @return resource
     */
    public function getResource();

    /**
     * Free resource when we are done with this result.
     *
     * @return bool
     */
    public function free();

    /**
     * Return row at $row_num.
     *
     * This function loads row at given position. When row is loaded, cursor is
     * set for the next row
     *
     * @param int $row_num
     *
     * @return array|LoadFromRow
     */
    public function getRowAt($row_num);

    /**
     * Set cursor to a given position in the record set.
     */
    public function seek(int $num): bool;

    /**
     * Return next record in result set.
     */
    public function next(): bool;

    /**
     * Return current row.
     *
     * @return mixed
     */
    public function getCurrentRow();

    /**
     * Return cursor position.
     *
     * @return int
     */
    public function getCursorPosition();

    /**
     * Returns DBResult indexed by value of a field or by result of specific
     * getter method.
     *
     * This function will treat $field_or_getter as field in case or array
     * return method, or as getter in case of object return method
     */
    public function toArrayIndexedBy(string $field_or_getter): array;

    /**
     * Return array of all rows.
     */
    public function toArray(): array;

    /**
     * Set a custom value caster.
     *
     * @param  ValueCasterInterface $value_caster
     * @return $this
     */
    public function &setValueCaster(ValueCasterInterface $value_caster);

    /**
     * Set result to return objects by class name.
     *
     * @param  string $class_name
     * @return $this
     */
    public function &returnObjectsByClass($class_name);

    /**
     * Set result to load objects of class based on filed value.
     *
     * @param  string $field_name
     * @return $this
     */
    public function &returnObjectsByField($field_name);
}
