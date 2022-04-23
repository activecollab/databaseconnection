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

namespace ActiveCollab\DatabaseConnection\Test\Fixture;

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;

/**
 * @property string $dependency
 */
class WriterWithContainer extends Writer implements ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;
}
