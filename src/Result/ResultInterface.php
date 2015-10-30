<?php

namespace ActiveCollab\DatabaseConnection\Result;

use IteratorAggregate;
use ArrayAccess;
use Countable;
use JsonSerializable;
use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;

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
     *
     * @param string $field_or_getter
     *
     * @return array
     */
    public function toArrayIndexedBy($field_or_getter);

    /**
     * Return array of all rows.
     *
     * @return array
     */
    public function toArray();

    /**
     * Set a custom value caster.
     *
     * @param ValueCasterInterface $value_caster
     */
    public function setValueCaster(ValueCasterInterface $value_caster);

    /**
     * Set result to return objects by class name.
     *
     * @param string $class_name
     */
    public function returnObjectsByClass($class_name);

    /**
     * Set result to load objects of class based on filed value.
     *
     * @param string $field_name
     */
    public function returnObjectsByField($field_name);
}