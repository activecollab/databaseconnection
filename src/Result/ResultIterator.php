<?php

  namespace ActiveCollab\DatabaseConnection\Result;

  use Iterator;

  /**
   * Class that lets PHP natively iterate over DB results
   *
   * @package angie.library.database
   */
  class ResultIterator implements Iterator
  {
    /**
     * Result set that is iterated
     *
     * @var Result
     */
    private $result;

    /**
     * Construct the iterator
     *
     * @param Result $result
     */
    public function __construct(Result $result)
    {
      $this->result = $result;
    }

    /**
     * If not at start of resultset, this method will call seek(0).
     * @see ResultSet::seek()
     */
    public function rewind()
    {
      if ($this->result->getCursorPosition() > 0) {
        $this->result->seek(0);
      }
    }

    /**
     * This method checks to see whether there are more results
     * by advancing the cursor position
     *
     * @return boolean
     */
    public function valid()
    {
      return $this->result->next();
    }

    /**
     * Returns the cursor position
     *
     * @return int
     */
    public function key()
    {
      return $this->result->getCursorPosition();
    }

    /**
     * Returns the row (assoc array) at current cursor position
     *
     * @return array
     */
    public function current()
    {
       return $this->result->getCurrentRow();
    }

    /**
     * This method does not actually do anything since we have already advanced
     * the cursor pos in valid()
     *
     * @return null
     */
    public function next()
    {
    }
  }
