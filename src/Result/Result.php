<?php

  namespace ActiveCollab\DatabaseConnection\Result;

  use ActiveCollab\DatabaseConnection\Connection;
  use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
  use ActiveCollab\DatabaseConnection\Record\ValueCaster;
  use IteratorAggregate;
  use ArrayAccess;
  use Countable;
  use JsonSerializable;
  use InvalidArgumentException;
  use BadMethodCallException;
  use mysqli_result;
  use ReflectionClass;

  /**
   * Abstraction of database query result
   *
   * @package angie.library.database
   */
  class Result implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable
  {
    /**
     * Cursor position
     *
     * @var integer
     */
    protected $cursor_position = 0;

    /**
     * Current row, set by
     *
     * @var integer|LoadFromRow
     */
    protected $current_row;

    /**
     * Database result resource
     *
     * @var resource
     */
    protected $resource;

    /**
     * Return mode
     *
     * @var integer
     */
    protected $return_mode;

    /**
     * Name of the class or field for return, if this result is returning
     * objects based on rows
     *
     * @var string
     */
    protected $return_class_or_field;

    /**
     * Construct a new result object from resource
     *
     * @param  mixed                    $resource
     * @param  integer                  $return_mode
     * @param  string                   $return_class_or_field
     * @throws InvalidArgumentException
     */
    public function __construct($resource, $return_mode = Connection::RETURN_ARRAY, $return_class_or_field = null)
    {
      if ($this->isValidResource($resource)) {
        $this->resource = $resource;

        if ($return_mode === Connection::RETURN_OBJECT_BY_CLASS) {
          if (!(new ReflectionClass($return_class_or_field))->implementsInterface('\ActiveCollab\DatabaseConnection\Record\LoadFromRow')) {
            throw new InvalidArgumentException("Class '$return_class_or_field' needs to implement LoadFromRow interface");
          }
        }

        $this->return_mode = $return_mode;
        $this->return_class_or_field = $return_class_or_field;
      } else {
        throw new InvalidArgumentException('mysqli_result expected');
      }
    }

    /**
     * Returns true if $resource is valid result resource
     *
     * @param  mixed $resource
     * @return bool
     */
    protected function isValidResource($resource)
    {
      return $resource instanceof mysqli_result && $resource->num_rows > 0;
    }

    /**
     * Free result on destruction
     */
    public function __destruct()
    {
      $this->free();
    }

    /**
     * Return resource
     *
     * @return resource
     */
    public function getResource()
    {
      return $this->resource;
    }

    /**
     * Set cursor to a given position in the record set
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
     * Return next record in result set
     *
     * @return array
     */
    public function next()
    {
      if ($this->cursor_position < $this->count() && $row = $this->resource->fetch_assoc()) { // Not count() - 1 because we use this for getting the current row
        $this->setCurrentRow($row);
        $this->cursor_position++;

        return true;
      }

      return false;
    }

    /**
     * Return number of records in result set
     *
     * @return integer
     */
    public function count()
    {
      return $this->resource->num_rows;
    }

    /**
     * Free resource when we are done with this result
     *
     * @return boolean
     */
    public function free()
    {
      if ($this->resource instanceof mysqli_result) {
        $this->resource->close();
      }
    }

    /**
     * Return row at $row_num
     *
     * This function loads row at given position. When row is loaded, cursor is
     * set for the next row
     *
     * @param  integer $row_num
     * @return mixed
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
     * Return cursor position
     *
     * @return integer
     */
    public function getCursorPosition()
    {
      return $this->cursor_position;
    }

    /**
     * Return current row
     *
     * @return mixed
     */
    public function getCurrentRow()
    {
      return $this->current_row;
    }

    /**
     * Set current row
     *
     * @param array $row
     */
    protected function setCurrentRow($row)
    {
      switch ($this->return_mode) {

        // Set object based on class name that we got in constructor
        case Connection::RETURN_OBJECT_BY_CLASS:
          $class_name = $this->return_class_or_field;

          $this->current_row = new $class_name();
          $this->current_row->loadFromRow($row);
          break;

        // Set object based on class name from field
        case Connection::RETURN_OBJECT_BY_FIELD:
          $class_name = $row[$this->return_class_or_field];

          $this->current_row = new $class_name();
          $this->current_row->loadFromRow($row);
          break;

        // Just return array
        default:
          $this->current_row = $row;
          $this->getValueCaster()->castRowValues($this->current_row);
      }
    }

    /**
     * Return array of all rows
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
     * Returns DBResult indexed by value of a field or by result of specific
     * getter method
     *
     * This function will treat $field_or_getter as field in case or array
     * return method, or as getter in case of object return method
     *
     * @param  string $field_or_getter
     * @return array
     */
    public function toArrayIndexedBy($field_or_getter)
    {
      $result = [];

      foreach ($this as $row) {
        if ($this->return_mode == Connection::RETURN_ARRAY) {
          $result[$row[$field_or_getter]] = $row;
        } else {
          $result[$row->$field_or_getter()] = $row;
        }
      }

      return $result;
    }

    /**
     * Return array or property => value pairs that describes this object
     *
     * @return array
     */
    public function jsonSerialize()
    {
      if ($this->count()) {
        $records = [];

        foreach ($this as $record) {
          if ($record instanceof JsonSerializable) {
            $records[] = $record->jsonSerialize();
          } else {
            $records[] = $record;
          }
        }

        return $records;
      } else {
        return [];
      }
    }

    // ---------------------------------------------------
    //  Casting
    // ---------------------------------------------------

    /**
     * @var ValueCaster
     */
    private $value_caser;

    /**
     * @return ValueCaster
     */
    private function &getValueCaster()
    {
      if (empty($this->value_caser)) {
        $this->value_caser = new ValueCaster();
      }

      return $this->value_caser;
    }

    /**
     * Set a custom value caster
     *
     * @param ValueCaster $value_caster
     */
    public function setValueCaster(ValueCaster $value_caster)
    {
      $this->value_caser = $value_caster;
    }

    // ---------------------------------------------------
    //  Return mode
    // ---------------------------------------------------

    /**
     * Set result to return objects by class name
     *
     * @param string $class_name
     */
    public function returnObjectsByClass($class_name)
    {
      $this->return_mode = Connection::RETURN_OBJECT_BY_CLASS;

      $this->return_class_or_field = $class_name;
    }

    /**
     * Set result to load objects of class based on filed value
     *
     * @param string $field_name
     */
    public function returnObjectsByField($field_name)
    {
      $this->return_mode = Connection::RETURN_OBJECT_BY_FIELD;

      $this->return_class_or_field = $field_name;
    }

    // ---------------------------------------------------
    //  Interface implementations
    // ---------------------------------------------------

    /**
     * Check if $offset exists
     *
     * @param  string  $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
      return $offset >= 0 && $offset < $this->count();
    }

    /**
     * Return value at $offset
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
      return $this->getRowAt($offset);
    }

    /**
     * Set value at $offset
     *
     * @param  integer|string         $offset
     * @param  mixed                  $value
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
      throw new BadMethodCallException('Database results are read only');
    }

    /**
     * Unset value at $offset
     *
     * @param  string                 $offset
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
      throw new BadMethodCallException('Database results are read only');
    }

    /**
     * Returns an iterator for for this object, for use with foreach
     *
     * @return ResultIterator
     */
    public function getIterator()
    {
      return new ResultIterator($this);
    }
  }