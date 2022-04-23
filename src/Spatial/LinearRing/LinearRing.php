<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\LinearRing;

use ActiveCollab\DatabaseConnection\Spatial\LineString\LineString;
use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use LogicException;

class LinearRing  extends LineString implements LinearRingInterface
{
    public function __construct(
        PointInterface ...$points
    )
    {
        if (count($points) < 4) {
            throw new LogicException('At least four points are required.');
        }

        if (!$points[0]->isSame($points[count($points) - 1])) {
            throw new LogicException('Linear ring is not closed.');
        }

        parent::__construct(...$points);
    }

    public function toWkt(): string
    {
        throw new LogicException('WKT is not available for line rings.');
    }
}
