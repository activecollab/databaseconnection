<?php

/*
 * This file is part of the Active Collab DatabaseStructure project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Test\Spatial;

use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use ActiveCollab\DatabaseConnection\Record\ValueCasterInterface;
use ActiveCollab\DatabaseConnection\Result\ResultInterface;
use ActiveCollab\DatabaseConnection\Spatial\LineString\LineString;
use ActiveCollab\DatabaseConnection\Spatial\LineString\LineStringInterface;
use ActiveCollab\DatabaseConnection\Spatial\MultiPolygon\MultiPolygon;
use ActiveCollab\DatabaseConnection\Spatial\MultiPolygon\MultiPolygonInterface;
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Spatial\Coordinate\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\Polygon;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\PolygonInterface;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;

class SpatialColumnsTest extends DbConnectedTestCase
{
    public function tearDown(): void
    {
        $this->connection->dropTable('points');
        $this->connection->dropTable('line_strings');
        $this->connection->dropTable('polygons');
        $this->connection->dropTable('multi_polygons');

        parent::tearDown();
    }

    public function testWillReadAndWritePoint(): void
    {
        $create_table = $this->connection->execute("CREATE TABLE IF NOT EXISTS `points` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `point` POINT NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $point_to_write = new Point(new Coordinate(25.774), new Coordinate(-80.19));

        $inserted = $this->connection->insert(
            'points',
            [
                'point' => $point_to_write,
            ]
        );

        $this->assertSame(1, $inserted);

        $rows = $this->connection->execute('SELECT `id`, ST_AsText(`point`) AS "point" FROM `points`');
        $this->assertInstanceOf(ResultInterface::class, $rows);

        $rows->setValueCaster(
            new ValueCaster(
                [
                    'point' => ValueCasterInterface::CAST_SPATIAL,
                ]
            )
        );

        $first_row = $rows[0];

        $this->assertInstanceOf(PointInterface::class, $first_row['point']);

        /** @var PointInterface $read_point */
        $read_point = $first_row['point'];

        $this->assertTrue($read_point->isSame($point_to_write));
    }

    public function testWillReadAndWriteLineString(): void
    {
        $create_table = $this->connection->execute("CREATE TABLE IF NOT EXISTS `line_strings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `line_string` LINESTRING NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $line_string_to_write =  new LineString(
            new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            new Point(new Coordinate(18.466), new Coordinate(-66.118)),
            new Point(new Coordinate(32.321), new Coordinate(-64.757)),
        );

        $inserted = $this->connection->insert(
            'line_strings',
            [
                'line_string' => $line_string_to_write,
            ]
        );

        $this->assertSame(1, $inserted);

        $rows = $this->connection->execute('SELECT `id`, ST_AsText(`line_string`) AS "line_string" FROM `line_strings`');
        $this->assertInstanceOf(ResultInterface::class, $rows);

        $rows->setValueCaster(
            new ValueCaster(
                [
                    'line_string' => ValueCasterInterface::CAST_SPATIAL,
                ]
            )
        );

        $first_row = $rows[0];

        $this->assertInstanceOf(LineStringInterface::class, $first_row['line_string']);

        /** @var LineStringInterface $read_line_string */
        $read_line_string = $first_row['line_string'];

        foreach ($read_line_string->getCoordinates() as $k => $read_coordinate) {
            $this->assertTrue(
                $read_coordinate->isSame(
                    $line_string_to_write->getCoordinates()[$k]
                )
            );
        }
    }

    public function testWillReadAndWritePolygon(): void
    {
        $create_table = $this->connection->execute("CREATE TABLE IF NOT EXISTS `polygons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `polygon` POLYGON NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $polygon_to_write =  new Polygon(
            new LinearRing(
                new Point(new Coordinate(25.774), new Coordinate(-80.19)),
                new Point(new Coordinate(18.466), new Coordinate(-66.118)),
                new Point(new Coordinate(32.321), new Coordinate(-64.757)),
                new Point(new Coordinate(25.774), new Coordinate(-80.19)),
            )
        );

        $inserted = $this->connection->insert(
            'polygons',
            [
                'polygon' => $polygon_to_write,
            ]
        );

        $this->assertSame(1, $inserted);

        $rows = $this->connection->execute('SELECT `id`, ST_AsText(`polygon`) AS "polygon" FROM `polygons`');
        $this->assertInstanceOf(ResultInterface::class, $rows);

        $rows->setValueCaster(
            new ValueCaster(
                [
                    'polygon' => ValueCasterInterface::CAST_SPATIAL,
                ]
            )
        );

        $first_row = $rows[0];

        $this->assertInstanceOf(PolygonInterface::class, $first_row['polygon']);

        /** @var PolygonInterface $read_polygon */
        $read_polygon = $first_row['polygon'];

        foreach ($read_polygon->getExteriorBoundary()->getCoordinates() as $k => $read_coordinate) {
            $this->assertTrue(
                $read_coordinate->isSame(
                    $polygon_to_write->getExteriorBoundary()->getCoordinates()[$k]
                )
            );
        }
    }

    public function testWillReadAndWriteMultiPolygon(): void
    {
        $this->connection->dropTable('multi_polygons');

        $create_table = $this->connection->execute("CREATE TABLE IF NOT EXISTS `multi_polygons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `multi_polygon` MULTIPOLYGON NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $multi_polygon_to_write = new MultiPolygon(
            new Polygon(
                new LinearRing(
                    new Point(new Coordinate(45.60317644), new Coordinate(19.27315063)),
                    new Point(new Coordinate(45.60312479), new Coordinate(19.27319189)),
                    new Point(new Coordinate(45.60473116), new Coordinate(19.27750116)),
                    new Point(new Coordinate(45.60478264), new Coordinate(19.27745963)),
                    new Point(new Coordinate(45.60317644), new Coordinate(19.27315063)),
                )
            ),

            new Polygon(
                new LinearRing(
                    new Point(new Coordinate(45.60449426), new Coordinate(19.27769178)),
                    new Point(new Coordinate(45.60431683), new Coordinate(19.27783455)),
                    new Point(new Coordinate(45.60270942), new Coordinate(19.27352285)),
                    new Point(new Coordinate(45.60288728), new Coordinate(19.27338113)),
                    new Point(new Coordinate(45.60449426), new Coordinate(19.27769178)),
                )
            )
        );

        $inserted = $this->connection->insert(
            'multi_polygons',
            [
                'multi_polygon' => $multi_polygon_to_write,
            ]
        );

        $this->assertSame(1, $inserted);

        $rows = $this->connection->execute('SELECT `id`, ST_AsText(`multi_polygon`) AS "multi_polygon" FROM `multi_polygons`');
        $this->assertInstanceOf(ResultInterface::class, $rows);

        $rows->setValueCaster(
            new ValueCaster(
                [
                    'multi_polygon' => ValueCasterInterface::CAST_SPATIAL,
                ]
            )
        );

        $first_row = $rows[0];

        $this->assertInstanceOf(MultiPolygonInterface::class, $first_row['multi_polygon']);

        /** @var MultiPolygonInterface $read_multi_polygon */
        $read_multi_polygon = $first_row['multi_polygon'];

        foreach ($read_multi_polygon as $k => $polygon) {
            foreach ($polygon->getExteriorBoundary()->getCoordinates() as $j => $read_coordinate) {
                $this->assertTrue(
                    $read_coordinate->isSame(
                        $multi_polygon_to_write->getPolygons()[$k]->getExteriorBoundary()->getCoordinates()[$j]
                    )
                );
            }
        }
    }
}
