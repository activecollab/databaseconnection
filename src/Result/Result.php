<?php

namespace ActiveCollab\DatabaseConnection\Result;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use BadMethodCallException;
use InvalidArgumentException;
use JsonSerializable;
use mysqli_result;
use ReflectionClass;

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
     * Constructor arguments (when objects are constructed from rows)
     *
     * @var array|null
     */
    private $constructor_arguments;

    /**
     * @var ValueCasterInterface
     */
    private $value_caser;

    /**
     * Construct a new result object from resource.
     *
     * @param  mysqli_result            $resource
     * @param  int                      $return_mode
     * @param  string                   $return_class_or_field
     * @param  array|null               $constructor_arguments
     * @throws InvalidArgumentException
     */
    public function __construct($resource, $return_mode = ConnectionInterface::RETURN_ARRAY, $return_class_or_field = null, array $constructor_arguments = null)
    {
        if (!$this->isValidResource($resource)) {
            throw new InvalidArgumentException('mysqli_result expected');
        }

        if ($return_mode === ConnectionInterface::RETURN_OBJECT_BY_CLASS) {
            if (!(new ReflectionClass($return_class_or_field))->implementsInterface('\ActiveCollab\DatabaseConnection\Record\LoadFromRow')) {
                throw new InvalidArgumentException("Class '$return_class_or_field' needs to implement LoadFromRow interface");
            }
        }

        $this->resource = $resource;
        $this->return_mode = $return_mode;
        $this->return_class_or_field = $return_class_or_field;
        $this->constructor_arguments = $constructor_arguments;
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
     *
     * @param  integer $num
     * @return boolean
     */
    public function seek($num)
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
     *
     * @return array
     */
    public function next()
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
     *
     * @return int
     */
    public function count()
    {
        return $this->resource->num_rows;
    }

    /**
     * Free resource when we are done with this result.
     *
     * @return bool
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
        } else {
            return null;
        }
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
     *
     * @param string $field_or_getter
     *
     * @return array
     */
    public function toArrayIndexedBy($field_or_getter)
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
     *
     * @return array
     */
    public function jsonSerialize()
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
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];

        foreach ($this as $row) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Check if $offset exists.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < $this->count();
    }

    /**
     * Return value at $offset.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getRowAt($offset);
    }

    /**
     * Set value at $offset.
     *
     * @param int|string $offset
     * @param mixed      $value
     *
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Database results are read only');
    }

    /**
     * Unset value at $offset.
     *
     * @param string $offset
     *
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Database results are read only');
    }

    /**
     * Returns an iterator for for this object, for use with foreach.
     *
     * @return ResultIterator
     */
    public function getIterator()
    {
        return new ResultIterator($this);
    }

    /**
     * Set a custom value caster.
     *
     * @param ValueCasterInterface $value_caster
     */
    public function setValueCaster(ValueCasterInterface $value_caster)
    {
        $this->value_caser = $value_caster;
    }

    /**
     * Set result to return objects by class name.
     *
     * @param string $class_name
     */
    public function returnObjectsByClass($class_name)
    {
        $this->return_mode = ConnectionInterface::RETURN_OBJECT_BY_CLASS;

        $this->return_class_or_field = $class_name;
    }

    /**
     * Set result to load objects of class based on filed value.
     *
     * @param string $field_name
     */
    public function returnObjectsByField($field_name)
    {
        $this->return_mode = ConnectionInterface::RETURN_OBJECT_BY_FIELD;

        $this->return_class_or_field = $field_name;
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
