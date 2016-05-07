<?php

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
