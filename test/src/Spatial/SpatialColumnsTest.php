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
use ActiveCollab\DatabaseConnection\Spatial\Coordinate\CoordinateInterface;
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Spatial\Coordinate\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\Polygon;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\PolygonInterface;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;

class SpatialColumnsTest extends DbConnectedTestCase
{
    public function setUp(): void
    {
        parent::setUp();


    }

    public function tearDown(): void
    {
        $this->connection->dropTable('points');
        $this->connection->dropTable('lines');
        $this->connection->dropTable('polygons');

        parent::tearDown();
    }

    public function testWillReadAndWritePoints(): void
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
}
