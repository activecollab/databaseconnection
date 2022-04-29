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
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;

class PointTest extends TestCase
{
    public function testWillRenderWtk(): void
    {
        $this->assertSame(
            'POINT(25.774 -80.19)',
            (new Point(new Coordinate(25.774), new Coordinate(-80.19)))->toWkt()
        );
    }

    public function testWillEncodeToJson(): void
    {
        $this->assertSame(
            [
                'type' => 'Point',
                'coordinates' => [
                    25.774,
                    -80.19,
                ]
            ],
            json_decode(json_encode(
                new Point(new Coordinate(25.774), new Coordinate(-80.19))),
                true
            ),
        );
    }
}
