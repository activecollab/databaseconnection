<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\LinearRing;

use ActiveCollab\DatabaseConnection\Spatial\Coordinates\CoordinateInterface;
use ActiveCollab\DatabaseConnection\Spatial\SpatialDataInterface;

interface LinearRingInterface extends SpatialDataInterface
{
    /**
     * @return CoordinateInterface[]
     */
    public function getCoordinates(): array;
}
