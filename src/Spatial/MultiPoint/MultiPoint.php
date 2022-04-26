<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\MultiPoint;

use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use LogicException;

class MultiPoint implements MultiPointInterface
{
    /**
     * @var PointInterface[]
     */
    private array $points;

    public function __construct(PointInterface ...$points)
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

    public function toWkt(): string
    {
        return sprintf('MULTIPOINT(%s)', $this);
    }

    public function __toString(): string
    {
        return implode(
            ',',
            array_map(
                function (PointInterface $coordinate) {
                    return (string) $coordinate;
                },
                $this->getPoints()
            )
        );
    }

    public function jsonSerialize(): array
    {
        return $this->points;
    }
}
