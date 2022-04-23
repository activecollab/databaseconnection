<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial;

use ActiveCollab\DatabaseConnection\Spatial\Coordinates\CoordinateInterface;

interface PolygonInterface
{
    /**
     * @return CoordinateInterface[]
     */
    public function getCoordinates(): array;
}
