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

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * @package ActiveCollab\DatabaseObject\Test\Fixtures
 */
class Container extends \Pimple\Container implements ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param  string $id Identifier of the entry to look for
     * @return mixed  Entry
     */
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->offsetGet($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }
}
