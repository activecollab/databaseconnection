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
use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Latitude;
use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Longitude;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;

class SpatialColumnsTest extends DbConnectedTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $create_table = $this->connection->execute("CREATE TABLE IF NOT EXISTS `polygons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `polygon` POLYGON NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);
    }

    public function tearDown(): void
    {
        $this->connection->dropTable('polygons');

        parent::tearDown();
    }

    public function testPolygonReadAndWrite(): void
    {
        $inserted = $this->connection->insert(
            'polygons',
            [
                'polygon' => new LinearRing(
                    new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
                    new Coordinate(new Latitude(18.466), new Longitude(-66.118)),
                    new Coordinate(new Latitude(32.321), new Longitude(-64.757)),
                    new Coordinate(new Latitude(25.774), new Longitude(-80.19)),
                )
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

        var_dump($first_row);
    }
}
