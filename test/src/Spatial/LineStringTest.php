<?php

/*
 * This file is part of the Active Collab DatabaseStructure project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Test\Spatial;

use ActiveCollab\DatabaseConnection\Spatial\Coordinate\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\LineString\LineString;
use ActiveCollab\DatabaseConnection\Spatial\LineString\LineStringInterface;
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;
use LogicException;

class LineStringTest extends TestCase
{
    public function testWillRequireAtLeastTwoPoints(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least two points are required.');

        new LineString(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
        );
    }

    public function testWillCreateLineString(): void
    {
        $line_string = new LineString(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
        );
        $this->assertInstanceOf(LineStringInterface::class, $line_string);
    }

    public function testWillRenderWtk(): void
    {
        $this->assertSame(
            'LINESTRING(25.774 -80.19,18.466 -66.118,32.321 -64.757)',
            (new LineString(
                new Point(new Coordinate(25.774), new Coordinate(-80.19)),
                new Point(new Coordinate(18.466), new Coordinate(-66.118)),
                new Point(new Coordinate(32.321), new Coordinate(-64.757)),
            ))->toWkt()
        );
    }
}
