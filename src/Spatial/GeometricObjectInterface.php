<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial;

use JsonSerializable;
use Stringable;

interface GeometricObjectInterface extends Stringable, JsonSerializable
{
    public function toWkt(): string;
}
