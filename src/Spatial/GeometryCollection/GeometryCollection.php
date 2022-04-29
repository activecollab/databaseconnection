<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\GeometryCollection;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;

class GeometryCollection implements GeometryCollectionInterface
{
    private array $elements;

    public function __construct(GeometricObjectInterface ...$elements)
    {
        $this->elements = $elements;
    }

    /**
     * @return GeometricObjectInterface[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }
}
