<?php

namespace ActiveCollab\DatabaseConnection\Record;

interface LoadFromRow
{
    /**
     * @param array $row
     */
    public function loadFromRow(array $row);
}
