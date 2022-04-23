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
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRingInterface;
use ActiveCollab\DatabaseConnection\Test\Base\DbLinkedTestCase;
use LogicException;

class LinearRingTest extends DbLinkedTestCase
{
    public function testWillRequireFourCoordinates(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least four coordinates are required.');

        new LinearRing(
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
            new Coordinate(new Latitude(18.466), new Longitude(-66.118)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
        );
    }

    public function testWilLRequireLinearRingToBeClosed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Linear ring is not closed.');

        new LinearRing(
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
            new Coordinate(new Latitude(18.466), new Longitude(-66.118)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
        );
    }

    public function testWillAcceptClosedLinearRing(): void
    {
        $linear_ring = new LinearRing(
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
            new Coordinate(new Latitude(18.466), new Longitude(-66.118)),
            new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
            new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
        );
        $this->assertInstanceOf(LinearRingInterface::class, $linear_ring);
    }
}
