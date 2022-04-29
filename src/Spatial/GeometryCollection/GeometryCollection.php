<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\GeometryCollection;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

class GeometryCollection implements GeometryCollectionInterface
{
    private array $geometries;

    public function __construct(GeometricObjectInterface ...$geometries)
    {
        $this->geometries = $geometries;
    }

    /**
     * @return GeometricObjectInterface[]
     */
    public function getGeometries(): array
    {
        return $this->geometries;
    }

    public function toWkt(): string
    {
        return '';
    }

    public function __toString(): string
    {
        return '';
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'GeometryCollection',
            'geometries' => $this->getGeometries(),
        ];
    }
}
