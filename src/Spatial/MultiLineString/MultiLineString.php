<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\MultiLineString;

use ActiveCollab\DatabaseConnection\Spatial\LineString\LineStringInterface;

class MultiLineString implements MultiLineStringInterface
{
    /**
     * @var LineStringInterface[]
     */
    private array $lines;

    public function __construct(LineStringInterface ...$lines)
    {
        $this->lines = $lines;
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    public function toWkt(): string
    {
        return sprintf('MULTILINESTRING(%s)', $this);
    }

    public function __toString(): string
    {
        return implode(
            ',',
            array_map(
                function (LineStringInterface $line_string) {
                    return sprintf('(%s)', $line_string);
                },
                $this->getLines()
            )
        );
    }
}
