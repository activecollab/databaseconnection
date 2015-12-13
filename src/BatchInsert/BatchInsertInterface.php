<?php

namespace ActiveCollab\DatabaseConnection\BatchInsert;

/**
 * @package ActiveCollab\DatabaseConnection\BatchInsert
 */
interface BatchInsertInterface
{
    /**
     * Return table name
     *
     * @return string
     */
    public function getTableName();

    /**
     * Return the list of files
     *
     * @return array
     */
    public function getFields();

    /**
     * @return int
     */
    public function getRowsPerBatch();

    /**
     * Return insert or replace mode (default is insert)
     *
     * @return string
     */
    public function getMode();

    /**
     * Insert a row with the given field values
     *
     * @param mixed ...$field_values
     */
    public function insert(...$field_values);

    /**
     * Insert array of already escaped values
     *
     * @param mixed ...$field_values
     */
    public function insertEscaped(...$field_values);

    /**
     * Insert rows that are already loaded
     */
    public function flush();

    /**
     * Finish with the batch
     */
    public function done();
}
