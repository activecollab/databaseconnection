<?php

/*
 * This file is part of the Active Collab Bootstrap project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\QueryLogger;

interface QueryLoggerInterface
{
    public function getNumberOfQueries(): int;
    public function getExecutionTime(): float;
}
