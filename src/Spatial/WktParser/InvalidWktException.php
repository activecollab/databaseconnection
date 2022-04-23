<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\WktParser;

use Exception;
use Throwable;

class InvalidWktException extends Exception
{
    public function __construct(string $text, Throwable $previous = null) {
        parent::__construct(
            sprintf(
                "Invalid WKT: '%s'",
                $text
            ),
            0,
            $previous
        );
    }
}
