<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\Polygon;

use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRingInterface;
use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface PolygonInterface extends GeometricObjectInterface
{
    public function getExteriorBoundary(): LinearRingInterface;
    public function getInnerBoundaries(): array;
}
