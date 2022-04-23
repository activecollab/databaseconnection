<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\MultiPoint;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface MultiPointInterface extends GeometricObjectInterface
{
    public function getPoints(): array;
}
