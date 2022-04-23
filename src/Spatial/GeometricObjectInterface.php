<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial;

use Stringable;

interface GeometricObjectInterface extends Stringable
{
    public function toWkt(): string;
}
