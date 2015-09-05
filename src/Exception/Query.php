<?php

  namespace ActiveCollab\DatabaseConnection\Exception;

  use Exception;

  /**
   * @package ActiveCollab\DatabaseConnection\Exception
   */
  class Query extends Exception
  {
    /**
     * Construct the exception
     *
     * @param string    $message
     * @param integer   $code
     * @param Exception $previous
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
      parent::__construct($message, $code, $previous);
    }
  }
