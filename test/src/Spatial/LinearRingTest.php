<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Test\Spatial;

use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Spatial\Coordinate\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRingInterface;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;
use LogicException;

class LinearRingTest extends TestCase
{
    public function testWillRequireFourPoints(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least four points are required.');

        new LinearRing(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
        );
    }

    public function testWilLRequireLinearRingToBeClosed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Linear ring is not closed.');

        new LinearRing(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
        );
    }

    public function testWillAcceptClosedLinearRing(): void
    {
        $linear_ring = new LinearRing(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
        );
        $this->assertInstanceOf(LinearRingInterface::class, $linear_ring);
    }
}
