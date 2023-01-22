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

namespace ActiveCollab\DatabaseConnection\Spatial\LineString;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface LineStringInterface extends GeometricObjectInterface
{
    public function getPoints(): array;
    public function getLines(): array;
}
