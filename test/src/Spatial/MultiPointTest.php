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
use ActiveCollab\DatabaseConnection\Spatial\MultiPoint\MultiPoint;
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;
use LogicException;

class MultiPointTest extends TestCase
{
    public function testWillRequireTwoPoints(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least two points are required.');

        new MultiPoint(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
        );
    }

    public function testWillRenderWtk(): void
    {
        $this->assertSame(
            'MULTIPOINT(25.774 -80.19,18.466 -66.118,32.321 -64.757)',
            (new MultiPoint(
                new Point(new Coordinate(25.774), new Coordinate(-80.19)),
                new Point(new Coordinate(18.466), new Coordinate(-66.118)),
                new Point(new Coordinate(32.321), new Coordinate(-64.757)),
            ))->toWkt()
        );
    }

    public function testWillEncodeToJson(): void
    {
        $multi_point = new MultiPoint(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
        );

        $this->assertSame(
            [
                'type' => 'MultiPoint',
                'coordinates' => [
                    [25.774, -80.19],
                    [18.466, -66.118],
                    [32.321, -64.757],
                ]
            ],
            json_decode(json_encode($multi_point), true),
        );
    }
}
