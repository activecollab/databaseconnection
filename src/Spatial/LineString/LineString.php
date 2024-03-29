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
    private array $points;

    public function __construct(
        PointInterface ...$points
    )
    {
        if (count($points) < 2) {
            throw new LogicException('At least two points are required.');
        }

        $this->points = $points;
    }

    public function getPoints(): array
    {
        return $this->points;
    }

    public function getLines(): array
    {
        $result = [];

        $previous_point = null;

        for ($i = 0; $i < count($this->points); $i++) {
            if (!empty($previous_point)) {
                $result[] = new LineString($previous_point, $this->points[$i]);
            }

            $previous_point = $this->points[$i];
        }

        return $result;
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
                $this->points
            )
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'LineString',
            'coordinates' => array_map(
                function (PointInterface $point) {
                    return [
                        $point->getX()->getValue(),
                        $point->getY()->getValue(),
                    ];
                },
                $this->getPoints(),
            )
        ];
    }
}
