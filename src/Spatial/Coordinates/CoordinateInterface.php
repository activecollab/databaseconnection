<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\Coordinates;

use ActiveCollab\DatabaseConnection\Spatial\SpatialDataInterface;

interface CoordinateInterface extends SpatialDataInterface
{
    public function getLatitude(): LatitudeInterface;
    public function getLongitude(): LongitudeInterface;
    public function isSame(CoordinateInterface $coordinate): bool;
}
