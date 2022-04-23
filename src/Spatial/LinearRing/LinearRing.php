<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\LinearRing;

use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use LogicException;

class LinearRing implements LinearRingInterface
{
    /**
     * @var PointInterface[]
     */
    private array $coordinates;

    public function __construct(
        PointInterface ...$coordinates
    )
    {
        if (count($coordinates) < 4) {
            throw new LogicException('At least four coordinates are required.');
        }

        if (!$coordinates[0]->isSame($coordinates[count($coordinates) - 1])) {
            throw new LogicException('Linear ring is not closed.');
        }

        $this->coordinates = $coordinates;
    }

    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    public function __toString(): string
    {
        return implode(
            ',',
            array_map(
                function (PointInterface $coordinate) {
                    return (string) $coordinate;
                },
                $this->coordinates
            )
        );
    }
}
