<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial;

use ActiveCollab\DatabaseConnection\Spatial\Coordinates\CoordinateInterface;
use LogicException;

class Polygon implements PolygonInterface
{
    /**
     * @var CoordinateInterface[]
     */
    private array $coordinates;

    public function __construct(
        CoordinateInterface ...$coordinates
    )
    {
        if (count($coordinates) < 4) {
            throw new LogicException('At least four coordinates are required.');
        }

        if (!$coordinates[0]->isSame($coordinates[count($coordinates) - 1])) {
            throw new LogicException('Polygon is not closed.');
        }

        $this->coordinates = $coordinates;
    }

    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    public function __toString(): string
    {
        return sprintf(
            'POLYGON((%s))',
            implode(
                ', ',
                array_map(
                    function (CoordinateInterface $coordinate) {
                        return sprintf(
                            '%d %d',
                            $coordinate->getLatitude()->getLatitude(),
                            $coordinate->getLongitude()->getLongitude()
                        );
                    },
                    $this->coordinates
                )
            )
        );
    }
}
