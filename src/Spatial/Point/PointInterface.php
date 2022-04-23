<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\Point;

use ActiveCollab\DatabaseConnection\Spatial\Coordinates\CoordinateInterface;
use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface PointInterface extends GeometricObjectInterface
{
    public function getX(): CoordinateInterface;
    public function getY(): CoordinateInterface;
    public function isSame(PointInterface $coordinate): bool;
}
