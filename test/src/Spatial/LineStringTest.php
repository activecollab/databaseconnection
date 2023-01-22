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

    public function testWillIterateLines(): void
    {
        $line_string = new LineString(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
        );

        $lines = $line_string->getLines();

        $this->assertCount(2, $lines);

        $this->assertInstanceOf(LineStringInterface::class, $lines[0]);
        $this->assertSame(
            (new LineString(
                new Point(new Coordinate(25.774), new Coordinate(-80.19)),
                new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            ))->toWkt(),
            $lines[0]->toWkt(),
        );

        $this->assertInstanceOf(LineStringInterface::class, $lines[1]);

        $this->assertSame(
            (new LineString(
                new Point(new Coordinate(18.466), new Coordinate(-66.118)),
                new Point(new Coordinate(32.321), new Coordinate(-64.757)),
            ))->toWkt(),
            $lines[1]->toWkt(),
        );
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

    public function testWillEncodeToJson(): void
    {
        $line_string = new LineString(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
        );

        $this->assertSame(
            [
                'type' => 'LineString',
                'coordinates' => [
                    [25.774, -80.19],
                    [18.466, -66.118],
                    [32.321, -64.757],
                ]
            ],
            json_decode(json_encode($line_string), true),
        );
    }
}
