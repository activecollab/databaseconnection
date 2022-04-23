<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\Coordinates;

class Coordinate implements CoordinateInterface
{
    public function __construct(
        private LatitudeInterface $latitude,
        private LongitudeInterface $longitude,
    )
    {
    }

    public function getLatitude(): LatitudeInterface
    {
        return $this->latitude;
    }

    public function getLongitude(): LongitudeInterface
    {
        return $this->longitude;
    }

    public function isSame(CoordinateInterface $coordinate): bool
    {
        return $coordinate->getLatitude()->getLatitude() === $this->getLatitude()->getLatitude() &&
            $coordinate->getLongitude()->getLongitude() === $this->getLongitude()->getLongitude();
    }
}