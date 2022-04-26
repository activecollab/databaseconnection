<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\Point;

use ActiveCollab\DatabaseConnection\Spatial\Coordinate\CoordinateInterface;

class Point implements PointInterface
{
    public function __construct(
        private CoordinateInterface $x_coordinate,
        private CoordinateInterface $y_coordinate,
    )
    {
    }

    public function getX(): CoordinateInterface
    {
        return $this->x_coordinate;
    }

    public function getY(): CoordinateInterface
    {
        return $this->y_coordinate;
    }

    public function isSame(PointInterface $coordinate): bool
    {
        return $coordinate->getX()->getValue() === $this->getX()->getValue() &&
            $coordinate->getY()->getValue() === $this->getY()->getValue();
    }

    public function toWkt(): string
    {
        return sprintf('POINT(%s)', $this);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s',
            $this->formatNumber($this->getX()->getValue()),
            $this->formatNumber($this->getY()->getValue()),
        );
    }

    private function formatNumber(float $number): string
    {
        return rtrim(
            number_format(
                $number,
                8,
                '.',
                ''
            ),
            '0'
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'x' => $this->getX(),
            'y' => $this->getY(),
        ];
    }
}
