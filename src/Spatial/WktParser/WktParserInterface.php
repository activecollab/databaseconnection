<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\WktParser;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

interface WktParserInterface
{
    public function parse(string $text): GeometricObjectInterface;
}
