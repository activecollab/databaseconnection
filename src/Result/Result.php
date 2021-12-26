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

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use BadMethodCallException;
use InvalidArgumentException;
use JsonSerializable;
use mysqli_result;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Traversable;

/**
 * Abstraction of database query result.
 */
class Result implements ResultInterface
{
    /**
     * Cursor position.
     *
     * @var int
     */
    private $cursor_position = 0;

    /**
     * Current row, set by.
     *
     * @var int|LoadFromRow
     */
    private $current_row;

    /**
     * Database result resource.
     *
     * @var mysqli_result
     */
    private $resource;

    /**
     * Return mode.
     *
     * @var int
     */
    private $return_mode;

    /**
     * Name of the class or field for return, if this result is returning
     * objects based on rows.
     *
     * @var string
     */
    private $return_class_or_field;

    /**
     * Constructor arguments (when objects are constructed from rows).
     *
     * @var array|null
     */
    private $constructor_arguments;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var ValueCasterInterface
     */
    private $value_caser;

    /**
     * Construct a new result object from resource.
     *
     * @param mysqli_result           $resource
     * @param int                     $return_mode
     * @param string                  $return_class_or_field
     * @param array|null              $constructor_arguments
     * @param ContainerInterface|null $container
     */
    public function __construct($resource, $return_mode = ConnectionInterface::RETURN_ARRAY, $return_class_or_field = null, array $constructor_arguments = null, ContainerInterface &$container = null)
    {
        if (!$this->isValidResource($resource)) {
            throw new InvalidArgumentException('mysqli_result expected');
        }

        if ($return_mode === ConnectionInterface::RETURN_OBJECT_BY_CLASS) {
            if (!(new ReflectionClass($return_class_or_field))->implementsInterface(LoadFromRow::class)) {
                throw new InvalidArgumentException("Class '$return_class_or_field' needs to implement LoadFromRow interface");
            }
        }

        $this->resource = $resource;
        $this->return_mode = $return_mode;
        $this->return_class_or_field = $return_class_or_field;
        $this->constructor_arguments = $constructor_arguments;
        $this->container = $container;
    }

    /**
     * Free result on destruction.
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * Return resource.
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set cursor to a given position in the record set.
     */
    public function seek(int $num): bool
    {
        if ($num >= 0 && $num <= $this->count() - 1) {
            if (!$this->resource->data_seek($num)) {
                return false;
            }

            $this->cursor_position = $num;

            return true;
        }

        return false;
    }

    /**
     * Return next record in result set.
     */
    public function next(): bool
    {
        if ($this->cursor_position < $this->count() && $row = $this->resource->fetch_assoc()) {
            // for getting the current row
            $this->setCurrentRow($row);
            ++$this->cursor_position;

            return true;
        }

        return false;
    }

    /**
     * Return number of records in result set.
     */
    public function count(): int
    {
        return $this->resource->num_rows;
    }

    /**
     * Free resource when we are done with this result.
     */
    public function free()
    {
        if ($this->resource instanceof mysqli_result) {
            $this->resource->close();
        }
    }

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
    public function getRowAt($row_num)
    {
        if ($this->seek($row_num)) {
            $this->next();

            return $this->getCurrentRow();
        }

        return null;
    }

    /**
     * Return cursor position.
     *
     * @return int
     */
    public function getCursorPosition()
    {
        return $this->cursor_position;
    }

    /**
     * Return current row.
     *
     * @return mixed
     */
    public function getCurrentRow()
    {
        return $this->current_row;
    }

    /**
     * Returns DBResult indexed by value of a field or by result of specific
     * getter method.
     *
     * This function will treat $field_or_getter as field in case or array
     * return method, or as getter in case of object return method
     */
    public function toArrayIndexedBy(string $field_or_getter): array
    {
        $result = [];

        foreach ($this as $row) {
            if ($this->return_mode === ConnectionInterface::RETURN_ARRAY) {
                $result[$row[$field_or_getter]] = $row;
            } else {
                $result[$row->$field_or_getter()] = $row;
            }
        }

        return $result;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): mixed
    {
        if (!$this->count()) {
            return [];
        }

        $records = [];

        foreach ($this as $record) {
            if ($record instanceof JsonSerializable) {
                $records[] = $record->jsonSerialize();
            } else {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Return array of all rows.
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this as $row) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Check if $offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset >= 0 && $offset < $this->count();
    }

    /**
     * Return value at $offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getRowAt($offset);
    }

    /**
     * Set value at $offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('Database results are read only');
    }

    /**
     * Unset value at $offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('Database results are read only');
    }

    /**
     * Returns an iterator for this object, for use with foreach.
     */
    public function getIterator(): Traversable
    {
        return new ResultIterator($this);
    }

    /**
     * {@inheritdoc}
     */
    public function &setValueCaster(ValueCasterInterface $value_caster)
    {
        $this->value_caser = $value_caster;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function &returnObjectsByClass($class_name)
    {
        $this->return_mode = ConnectionInterface::RETURN_OBJECT_BY_CLASS;
        $this->return_class_or_field = $class_name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function &returnObjectsByField($field_name)
    {
        $this->return_mode = ConnectionInterface::RETURN_OBJECT_BY_FIELD;
        $this->return_class_or_field = $field_name;

        return $this;
    }

    /**
     * Returns true if $resource is valid result resource.
     *
     * @param mixed $resource
     *
     * @return bool
     */
    protected function isValidResource($resource)
    {
        return $resource instanceof mysqli_result && $resource->num_rows > 0;
    }

    /**
     * Set current row.
     *
     * @param array $row
     */
    protected function setCurrentRow($row)
    {
        if (!in_array($this->return_mode, [ConnectionInterface::RETURN_OBJECT_BY_CLASS, ConnectionInterface::RETURN_OBJECT_BY_FIELD], true)) {
            $this->current_row = $row;
            $this->getValueCaster()->castRowValues($this->current_row);

            return;
        }

        $class_name = $this->return_mode === ConnectionInterface::RETURN_OBJECT_BY_CLASS
            ? $this->return_class_or_field
            : $row[$this->return_class_or_field];

        if (empty($this->constructor_arguments)) {
            $this->current_row = new $class_name();
        } else {
            $this->current_row = (new ReflectionClass($class_name))->newInstanceArgs($this->constructor_arguments);
        }

        if ($this->current_row instanceof ContainerAccessInterface && $this->container) {
            $this->current_row->setContainer($this->container);
        }

        $this->current_row->loadFromRow($row);
    }

    /**
     * @return ValueCasterInterface
     */
    private function getValueCaster()
    {
        if (empty($this->value_caser)) {
            $this->value_caser = new ValueCaster();
        }

        return $this->value_caser;
    }
}
