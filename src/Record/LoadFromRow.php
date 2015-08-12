<?php

  namespace ActiveCollab\DatabaseConnection\Record;

  /**
   * @package ActiveCollab\DatabaseConnection\Record
   */
  interface LoadFromRow
  {
    /**
     * @param array $row
     */
    public function loadFromRow(array $row);
  }