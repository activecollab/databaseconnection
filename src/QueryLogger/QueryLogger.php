<?php

/*
 * This file is part of the Feud project.
 *
 * (c) PhpCloud.org Core Team <core@phpcloud.org>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\QueryLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class QueryLogger implements QueryLoggerInterface
{
    private array $queries = [];
    private float $executionTime = 0.0;

    public function __construct(
        private LoggerInterface $logger,
        private string $logLevel = LogLevel::DEBUG,
    )
    {
    }

    public function __invoke(string $querySql, float $queryExecutionTime): void
    {
        $this->logger->log(
            $this->logLevel,
            'Query {query} ran in {time}s.',
            [
                'query' => $querySql,
                'time' => round($queryExecutionTime, 5),
            ],
        );

        $this->queries[] = $querySql;
        $this->executionTime += $queryExecutionTime;
    }

    public function getNumberOfQueries(): int
    {
        return count($this->queries);
    }

    public function getExecutionTime(): float
    {
        return round($this->executionTime, 5);
    }
}
