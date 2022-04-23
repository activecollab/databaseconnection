<?php

/*
 * This file is part of the Active Collab DatabaseStructure project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Test\Spatial;

use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Latitude;
use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Longitude;
use ActiveCollab\DatabaseConnection\Spatial\Polygon;
use ActiveCollab\DatabaseConnection\Spatial\PolygonInterface;
use ActiveCollab\DatabaseConnection\Test\TestCase;
use LogicException;

class PolygonTest extends TestCase
{
    public function testWillRequireFourCoordinates(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least four coordinates are required.');

        new Polygon(
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
            new Coordinate(new Latitude(18.466), new Longitude(-66.118)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
        );
    }

    public function testWilLRequirePolygonToBeClosed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Polygon is not closed.');

        new Polygon(
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
            new Coordinate(new Latitude(18.466), new Longitude(-66.118)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
        );
    }

    public function testWillAcceptClosedPolygon(): void
    {
        $polygon = new Polygon(
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
            new Coordinate(new Latitude(18.466), new Longitude(-66.118)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
        );
        $this->assertInstanceOf(PolygonInterface::class, $polygon);
    }
}
