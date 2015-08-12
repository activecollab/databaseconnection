<?php

  namespace ActiveCollab\DatabaseConnection\Test\Support;

  use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
  use DateTime;

  /**
   * @package ActiveCollab\DatabaseConnection\Test\Support
   */
  class Writer implements LoadFromRow
  {
    /**
     * @var array
     */
    private $row;

    /**
     * @param array $row
     */
    public function loadFromRow(array $row)
    {
      $this->row = $row;
    }

    /**
     * @return integer
     */
    public function getId()
    {
      return $this->row['id'];
    }

    /**
     * @return string
     */
    public function getName()
    {
      return $this->row['name'];
    }

    /**
     * @return DateTime
     */
    public function getBirthday()
    {
      return new DateTime($this->row['birthday']);
    }
  }