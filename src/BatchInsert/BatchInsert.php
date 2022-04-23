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

namespace ActiveCollab\DatabaseConnection\BatchInsert;

use ActiveCollab\DatabaseConnection\ConnectionInterface;
use BadMethodCallException;
use InvalidArgumentException;
use RuntimeException;

class BatchInsert implements BatchInsertInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $table_name;

    /**
     * @var array
     */
    private $fields;

    /**
     * Cached SQL query foundation.
     *
     * @var string
     */
    private $sql_foundation;

    /**
     * String that's used to prepare statements.
     *
     * @var string
     */
    private $row_prepare_pattern;

    /**
     * Number of fields that are being inserted.
     *
     * @var int
     */
    private $fields_num;

    /**
     * Numbe of rows that are inserted per single INSERT query.
     *
     * @var int
     */
    private $rows_per_batch;

    /**
     * Insert or replace mode.
     *
     * @var string
     */
    private $mode;

    /**
     * Array of rows that will need to be inserted into the database.
     *
     * @var array
     */
    private $rows = [];

    /**
     * Total number of rows inserted.
     *
     * @var int
     */
    private $total = 0;

    /**
     * @var bool
     */
    private $is_done = false;

    /**
     * @param ConnectionInterface $connection
     * @param string              $table_name
     * @param string[]            $fields
     * @param int                 $rows_per_batch
     * @param string              $mode
     */
    public function __construct(ConnectionInterface $connection, $table_name, array $fields, $rows_per_batch = 50, $mode = ConnectionInterface::INSERT)
    {
        if (empty($fields)) {
            throw new InvalidArgumentException('Array of fields expected');
        }

        if ($mode != ConnectionInterface::INSERT && $mode != ConnectionInterface::REPLACE) {
            throw new InvalidArgumentException("Mode '$mode' is not a valid batch insert mode");
        }

        $this->connection = $connection;
        $this->table_name = $table_name;
        $this->fields = $fields;

        $this->fields_num = count($fields);

        $escaped_field_names = $question_marks = [];

        foreach ($fields as $k => $v) {
            $escaped_field_names[] = $this->connection->escapeFieldName($v);
            $question_marks[] = '?';
        }

        if ($mode == ConnectionInterface::REPLACE) {
            $this->sql_foundation = 'REPLACE INTO ' . $this->connection->escapeTableName($table_name) . ' (' . implode(', ', $escaped_field_names) . ') VALUES ';
        } else {
            $this->sql_foundation = 'INSERT INTO ' . $this->connection->escapeTableName($table_name) . ' (' . implode(', ', $escaped_field_names) . ') VALUES ';
        }
        $this->row_prepare_pattern = '(' . implode(', ', $question_marks). ')';
        $this->rows_per_batch = (int) $rows_per_batch;

        if ($this->rows_per_batch < 1) {
            $this->rows_per_batch = 50;
        }

        $this->mode = $mode;
    }

    /**
     * Return table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * Return the list of files.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return int
     */
    public function getRowsPerBatch()
    {
        return $this->rows_per_batch;
    }

    /**
     * Return insert or replace mode (default is insert).
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Insert a row with the given field values.
     *
     * @param mixed ...$field_values
     */
    public function insert(...$field_values)
    {
        if ($this->is_done) {
            throw new RuntimeException('This batch insert is already done');
        }

        if (count($field_values) == $this->fields_num) {
            $this->rows[] = $this->connection->prepare($this->row_prepare_pattern, ...$field_values);

            if (count($this->rows) == $this->rows_per_batch) {
                $this->flush();
            }
        } else {
            throw new BadMethodCallException('Number of arguments does not match number of fields');
        }
    }

    /**
     * Insert array of already escaped values.
     *
     * @param mixed ...$field_values
     */
    public function insertEscaped(...$field_values)
    {
        if ($this->is_done) {
            throw new RuntimeException('This batch insert is already done');
        }

        if (count($field_values) == $this->fields_num) {
            $this->rows[] = '(' . implode(', ', $field_values) . ')';

            $this->checkAndInsert();
        } else {
            throw new BadMethodCallException('Number of arguments does not match number of fields');
        }
    }

    /**
     * Insert rows that are already loaded.
     */
    public function flush()
    {
        if ($this->is_done) {
            throw new RuntimeException('This batch insert is already done');
        }

        $count_rows = count($this->rows);

        if ($count_rows > 0) {
            $this->connection->execute($this->sql_foundation . implode(', ', $this->rows));

            $this->rows = [];
            $this->total += $count_rows;
        }
    }

    /**
     * Finish with the batch.
     */
    public function done()
    {
        if ($this->is_done) {
            throw new RuntimeException('This batch insert is already done');
        }

        $this->flush();
        $this->is_done = true;

        return $this->total;
    }

    /**
     * Check whether we should insert rows and insert them if we do.
     */
    private function checkAndInsert()
    {
        if (count($this->rows) == $this->rows_per_batch) {
            $this->flush();
        }
    }
}
