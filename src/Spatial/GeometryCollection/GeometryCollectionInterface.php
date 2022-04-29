<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\GeometryCollection;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface GeometryCollectionInterface extends GeometricObjectInterface
{
    /**
     * @return GeometricObjectInterface[]
     */
    public function getGeometries(): array;
}
