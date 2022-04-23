<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\MultiPolygon;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface MultiPolygonInterface extends GeometricObjectInterface
{
    public function getPolygons(): array;
}
