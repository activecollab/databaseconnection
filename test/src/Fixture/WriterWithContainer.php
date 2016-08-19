<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Test\Fixture;

use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;

/**
 *  @property string $dependency
 *
 * @package ActiveCollab\DatabaseConnection\Test\Fixture
 */
class WriterWithContainer extends Writer implements ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;
}
