<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\LinearRing;

use ActiveCollab\DatabaseConnection\Spatial\LineString\LineStringInterface;
use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;

interface LinearRingInterface extends LineStringInterface
{
    /**
     * @return PointInterface[]
     */
    public function getPoints(): array;
}
