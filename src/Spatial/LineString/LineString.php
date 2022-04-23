<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\LineString;

use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use LogicException;

class LineString implements LineStringInterface
{
    /**
     * @var PointInterface[]
     */
    private array $coordinates;

    public function __construct(
        PointInterface ...$coordinates
    )
    {
        if (count($coordinates) < 2) {
            throw new LogicException('At least two points are required.');
        }

        $this->coordinates = $coordinates;
    }

    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    public function toWkt(): string
    {
        return sprintf('LINESTRING(%s)', $this);
    }

    public function __toString(): string
    {
        return implode(
            ',',
            array_map(
                function (PointInterface $coordinate) {
                    return (string) $coordinate;
                },
                $this->coordinates
            )
        );
    }
}
