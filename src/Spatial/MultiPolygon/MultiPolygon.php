<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\MultiPolygon;

use ActiveCollab\DatabaseConnection\Spatial\Polygon\PolygonInterface;

class MultiPolygon implements MultiPolygonInterface
{
    /**
     * @var PolygonInterface[]
     */
    private array $polygons;

    public function __construct(PolygonInterface ...$polygons)
    {
        $this->polygons = $polygons;
    }

    public function getPolygons(): array
    {
        return $this->polygons;
    }

    public function toWkt(): string
    {
        return sprintf('MULTIPOLYGON(%s)', $this);
    }

    public function __toString(): string
    {
        return implode(
            ',',
            array_map(
                function (PolygonInterface $polygon) {
                    return sprintf('(%s)', $polygon);
                },
                $this->getPolygons()
            )
        );
    }

    public function jsonSerialize(): array
    {
        return $this->polygons;
    }
}
