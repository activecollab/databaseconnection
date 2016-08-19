<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Result;

use Iterator;

/**
 * Class that lets PHP natively iterate over DB results.
 */
class ResultIterator implements Iterator
{
    /**
     * Result set that is iterated.
     *
     * @var ResultInterface
     */
    private $result;

    /**
     * Construct the iterator.
     *
     * @param ResultInterface $result
     */
    public function __construct(ResultInterface $result)
    {
        $this->result = $result;
    }

    /**
     * If not at start of resultset, this method will call seek(0).
     *
     * @see ResultSet::seek()
     */
    public function rewind()
    {
        if ($this->result->getCursorPosition() > 0) {
            $this->result->seek(0);
        }
    }

    /**
     * This method checks to see whether there are more results
     * by advancing the cursor position.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->result->next();
    }

    /**
     * Returns the cursor position.
     *
     * @return int
     */
    public function key()
    {
        return $this->result->getCursorPosition();
    }

    /**
     * Returns the row (assoc array) at current cursor position.
     *
     * @return array
     */
    public function current()
    {
        return $this->result->getCurrentRow();
    }

    /**
     * This method does not actually do anything since we have already advanced
     * the cursor pos in valid().
     */
    public function next()
    {
    }
}
