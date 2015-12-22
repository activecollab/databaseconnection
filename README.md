# DatabaseConnection Library

[![Build Status](https://travis-ci.org/activecollab/databaseconnection.svg?branch=master)](https://travis-ci.org/activecollab/databaseconnection)

Purpose of this library is not to abstract the database, but to make work with MySQLi connections a bit easier. Features:

1. Results that can be easily iterated,
2. Results can be arrays of rows, or objects, loaded by a known class name, or by a class name read from the row field,
3. Automatic value casting based on field name.

What's the thinking behind yet another database abstraction layer? Focus and history. This library has been part of [Active Collab](https://www.activecollab.com) for many years, so it works really well. On the other hand, it's simple - works only with MySQL, can be read and understood in an hour and still manages to save you a lot of time.

## Getting the Data

This library makes query execution quick and easy. You can fetch all records, only first record, only first column or only first cell (first column of the first record). Here's a couple of examples:

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

## Counting Records

DatabaseConnection lets you easily count records from the table:

```php
$num = $connection->count('writers');
```

By default, it returns number of all records from the table. To filter, you can provide `$conditions` argument:

```php
$num = $connection->count('writers', "`name` = 'Leo Tolstoy''");
$num = $this->connection->count('writers', ['name = ?', 'Leo Tolstoy']);
```

`count()` method also assumes that there's `id` primary key in the table, so it prepares query as `COUNT(id)`. Name of
the column that we count agains can also be changed (even to `*`):


```php
$num = $this->connection->count('writers', null, '*');
$num = $this->connection->count('writers', null, 'name')
```

## Batch Inserts

Batch insert utility helps you prepare and insert a lot of rows of the same type. Example:

```php
// Insert 3 rows per INSERT query in `writers` table
$batch_insert = $connection->batchInsert('writers', ['name', 'birthday'], 3);

$batch_insert->insert('Leo Tolstoy', new DateTime('1828-09-09')); // No insert
$batch_insert->insert('Alexander Pushkin', new DateTime('1799-06-06')); // No insert
$batch_insert->insert('Fyodor Dostoyevsky', new DateTime('1821-11-11')); // Insert
$batch_insert->insert('Anton Chekhov', new DateTime('1860-01-29')); // No insert

$batch_insert->done(); // Insert remaining rows and close the batch insert
```

Note: Calling `insert`, `insertEscaped`, `flush` or `done` methods once batch is done will throw `RuntimeException`.

Batch insert can also be used to replace records (uses `REPLACE INTO` instead of INSERT INTO queries):

```php
$batch_replace = $this->connection->batchInsert('writers', ['name', 'birthday'], 3, ConnectionInterface::REPLACE);
```

## Casting

Unless specified differently, following conventions apply:

1. `id` and `row_count` fields are always cast to integers,
2. Fields with name ending with `_id` are cast to integers,
3. Fields with name starting with `is_` are cast to boolean,
4. Fields with name ending with `_at` or `_on` are cast to DateValue.

## Object Hydration

This library enables quick and easy object hydration. To hydrate objects, you'll need a class that implements `\ActiveCollab\DatabaseConnection\Record\LoadFromRow` interface, for example:

```php
<?php

use ActiveCollab\DatabaseConnection\Record\LoadFromRow;
use DateTime;

class Writer implements LoadFromRow
{
  /**
   * @var array
   */
  private $row;

  /**
   * @param array $row
   */
  public function loadFromRow(array $row)
  {
    $this->row = $row;
  }

  /**
   * @return integer
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
```

You can get a list of hydrated objects by passing the name of the class to `advancedExecute()` method:

```php
<?php

use ActiveCollab\DatabaseConnection\Connection;
use DateTime;
use MySQLi;
use RuntimeException;

$database_link = new MySQLi('localhost', 'root', '', 'activecollab_database_connection_test');

if ($database_link->connect_error) {
  throw new RuntimeException('Failed to connect to database. MySQL said: ' . $database_link->connect_error);
}

$connection = new Connection($database_link);

foreach ($this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_CLASS, '\ActiveCollab\DatabaseConnection\Test\Fixture\Writer') as $writer) {
  print '#' . $writer->getId() . ' ' . $writer->getName() . ' (' . $writer->getBirthday()->format('Y-m-d') . ')';
}
```

If you store objects of multiple types in the same table, you can tell `advancedExecute()` method where to look for the class name. This example assumes that we store the full class name in `type` field:

```php
<?php

use ActiveCollab\DatabaseConnection\Connection;
use DateTime;
use MySQLi;
use RuntimeException;

$database_link = new MySQLi('localhost', 'root', '', 'activecollab_database_connection_test');

if ($database_link->connect_error) {
  throw new RuntimeException('Failed to connect to database. MySQL said: ' . $database_link->connect_error);
}

$connection = new Connection($database_link);

foreach ($this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_FIELD, 'type') as $writer) {
  print '#' . $writer->getId() . ' ' . $writer->getName() . ' (' . $writer->getBirthday()->format('Y-m-d') . ')';
}
```

## Object Hydration + DIC

If objects that you are hydrating implement `ActiveCollab\ContainerAccess` interface, and you pass an `Interop\Container\ContainerInterface` instance to `advancedExecute()` method, container will be provided to all instances that result hydrates:

```php
use ActiveCollab\DatabaseConnection\Test\Fixture\Container;
use ActiveCollab\DatabaseConnection\Test\Fixture\WriterWithContainer;
use Interop\Container\ContainerInterface;

$container = new Container([
    'dependency' => 'it works!'
]);

$result = $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_CLASS, WriterWithContainer::class, null, $container);

foreach ($result as $writer) {
    print $writer->dependency . "\n"; // Prints 'it works!'
}
```

## Tests

To test a library you need to manually create a database:

```CREATE DATABASE activecollab_database_connection_test DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;```

Then from a project root execute following command:

```databaseconnection$ phpunit -c test```

## To do

1. Use prepared statements for all queries that have extra arguments,
2. Enable library to use two database connections, one for writes, and the other for reads,
3. Properly handle MySQL has gone away errors and deadlocks (stubbed).
