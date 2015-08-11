<?php

  namespace ActiveCollab\DatabaseConnection;

  use Countable;

  /**
   * @package ActiveCollab\DatabaseConnection
   */
  class Result implements Countable
  {
    /**
     * @return int
     */
    public function count()
    {
      return 0;
    }
  }