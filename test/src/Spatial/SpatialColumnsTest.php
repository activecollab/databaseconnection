<?php

/*
 * This file is part of the Active Collab DatabaseStructure project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Test\Spatial;

use ActiveCollab\DatabaseConnection\Test\Base\DbConnectedTestCase;

class SpatialColumnsTest extends DbConnectedTestCase
{
    public function setUp(): void
    {
        parent::setUp();


    }

    public function testPolygonReadAndWrite(): void
    {

    }
}
