# DatabaseConnection Library

[![Build Status](https://travis-ci.org/activecollab/databaseconnection.svg?branch=master)](https://travis-ci.org/activecollab/databaseconnection)

Purpose of this library is not to abstract the database, but to make work with MySQLi connections a bit easier. Features:

1. Results that can be easily iterated,
2. Results can be arrays of rows, or objects, loaded by a known class name, or by a class name read from the row field,
3. Automatic value casting based on field name

## Iterating

```php
<?php

use ActiveCollab\DatabaseConnection\Connection;
use MySQLi;
use RuntimeException;

$database_link = new MySQLi('localhost', 'root', '', 'activecollab_database_connection_test');

if ($database_link->connect_error) {
  throw new RuntimeException('Failed to connect to database. MySQL said: ' . $database_link->connect_error);
}

$connection = new Connection($database_link);

// List all writers
foreach ($connection->execute('SELECT `id`, `name` FROM `writers` WHERE `name` = ? ORDER BY `id`', 'Leo Tolstoy') as $row) {
  print '#' . $row['id'] . ' ' . $row['name'] . "\n";
}

// Get the first cell of the first row (so we can print Tolstoy's birthday)
print $connection->executeFirstCell('SELECT `birthday` FROM `writers` WHERE `name` = ? LIMIT 0, 1', 'Leo Tolstoy');

// Get everything that we have on Leo Tolstoy
print_r($connection->executeFirstRow('SELECT * FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));

// Show names of all authors
print_r($connection->executeFirstColumn('SELECT `name` FROM `writers` ORDER BY `name`'));
```

## Casting

Unless specified differently, following conventions apply:

1. `id` and `row_count` fields are always cast to integers,
2. Fields with name ending with `_id` are cast to integers,
3. Fields with name starting with `is_` are cast to boolean,
4. Fields with name ending with `_at` or `_on` are cast to DateValue,