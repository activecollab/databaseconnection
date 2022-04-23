<?php

/*
 * This file is part of the Active Collab DatabaseStructure project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Test\Spatial;

use ActiveCollab\DatabaseConnection\Spatial\Coordinate\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\Polygon;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;

class PolygonTest extends TestCase
{
    public function testWillRenderWtk(): void
    {
        $this->assertSame(
            'POLYGON((25.774 -80.19,18.466 -66.118,32.321 -64.757,25.774 -80.19))',
            (new Polygon(
                new LinearRing(
                    new Point(new Coordinate(25.774), new Coordinate(-80.19)),
                    new Point(new Coordinate(18.466), new Coordinate(-66.118)),
                    new Point(new Coordinate(32.321), new Coordinate(-64.757)),
                    new Point(new Coordinate(25.774), new Coordinate(-80.19)),
                )
            ))->toWkt()
        );
    }
}
