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
use ActiveCollab\DatabaseConnection\Spatial\MultiLineString\MultiLineString;
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;

class MultiLineStringTest extends TestCase
{
    public function testWillRenderWtk(): void
    {
        $this->assertSame(
            'MULTILINESTRING((45.60317644 19.27315063,45.60312479 19.27319189,45.60473116 19.27750116,45.60478264 19.27745963),(45.60449426 19.27769178,45.60431683 19.27783455,45.60270942 19.27352285,45.60288728 19.27338113))',
            (new MultiLineString(
                new LineString(
                    new Point(new Coordinate(45.60317644), new Coordinate(19.27315063)),
                    new Point(new Coordinate(45.60312479), new Coordinate(19.27319189)),
                    new Point(new Coordinate(45.60473116), new Coordinate(19.27750116)),
                    new Point(new Coordinate(45.60478264), new Coordinate(19.27745963)),
                ),

                new LineString(
                    new Point(new Coordinate(45.60449426), new Coordinate(19.27769178)),
                    new Point(new Coordinate(45.60431683), new Coordinate(19.27783455)),
                    new Point(new Coordinate(45.60270942), new Coordinate(19.27352285)),
                    new Point(new Coordinate(45.60288728), new Coordinate(19.27338113)),
                ),
            ))->toWkt()
        );
    }
}
