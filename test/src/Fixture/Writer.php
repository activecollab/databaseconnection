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

use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use DateTime;

/**
 * @package ActiveCollab\DatabaseConnection\Test\Fixture
 */
class Writer implements LoadFromRow
{
    /**
     * @var array
     */
    private $row;

    /**
     * @var mixed
     */
    public $constructor_argument_1, $constructor_argument_2;

    /**
     * @param mixed $constructor_argument_1
     * @param mixed $constructor_argument_2
     */
    public function __construct($constructor_argument_1 = null, $constructor_argument_2 = null)
    {
        $this->constructor_argument_1 = $constructor_argument_1;
        $this->constructor_argument_2 = $constructor_argument_2;
    }

    /**
     * @param array $row
     */
    public function loadFromRow(array $row)
    {
        $this->row = $row;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->row['id'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->row['name'];
    }

    /**
     * @return DateTime
     */
    public function getBirthday()
    {
        return new DateTime($this->row['birthday']);
    }
}
