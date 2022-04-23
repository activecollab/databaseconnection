<?php

/*
 * This file is part of the Active Collab DatabaseStructure project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\LineString;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface LineStringInterface extends GeometricObjectInterface
{
    public function getCoordinates(): array;
}
