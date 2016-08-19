<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Exception;

use Exception;

/**
 * @package ActiveCollab\DatabaseConnection\Exception
 */
class QueryException extends Exception implements ExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
