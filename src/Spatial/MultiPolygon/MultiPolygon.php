<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\MultiPolygon;

use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRingInterface;
use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
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
        return [
            'type' => 'MultiPolygon',
            'coordinates' => array_map(
                function (PolygonInterface $polygon) {
                    return array_map(
                        function (LinearRingInterface $linear_ring) {
                            return array_map(
                                function (PointInterface $point) {
                                    return [
                                        $point->getX()->getValue(),
                                        $point->getY()->getValue(),
                                    ];
                                },
                                $linear_ring->getPoints(),
                            );
                        },
                        array_merge(
                            [
                                $polygon->getExteriorBoundary(),
                            ],
                            $polygon->getInnerBoundaries(),
                        )
                    );
                },
                $this->getPolygons(),
            )
        ];
    }
}
