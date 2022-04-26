<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\Polygon;

use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRingInterface;

class Polygon implements PolygonInterface
{
    /**
     * @var LinearRingInterface[]
     */
    private array $inner_boundaries;

    public function __construct(
        private LinearRingInterface $exterior_boundary,
        LinearRingInterface ...$inner_boundaries
    )
    {
        $this->inner_boundaries = $inner_boundaries;
    }

    public function getExteriorBoundary(): LinearRingInterface
    {
        return $this->exterior_boundary;
    }

    public function getInnerBoundaries(): array
    {
        return $this->inner_boundaries;
    }

    public function toWkt(): string
    {
        return sprintf('POLYGON(%s)', $this);
    }

    public function __toString(): string
    {
        $boundaries = [
            sprintf('(%s)', $this->getExteriorBoundary()),
        ];

        foreach ($this->getInnerBoundaries() as $inner_boundary) {
            $boundaries[] = sprintf('(%s)', $inner_boundary);
        }

        return implode(', ', $boundaries);
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            [
                $this->exterior_boundary,
            ],
            $this->inner_boundaries
        );
    }
}
