<?php

namespace ActiveCollab\DatabaseConnection\Exception;

use Exception;

/**
 * @package ActiveCollab\DatabaseConnection\Exception
 */
class ConnectionException extends Exception implements ExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
