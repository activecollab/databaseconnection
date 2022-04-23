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

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\Exception\QueryException;
use ActiveCollab\DatabaseConnection\Test\Base\TestCase;

class FieldsTest extends TestCase
{
    /**
     * @var MysqliConnection
     */
    private $connection;

    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);

        $this->connection->execute('DROP TABLE IF EXISTS `writers`');
        $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        parent::tearDown();
    }

    /**
     * Test get field names.
     */
    public function testGetFieldNames()
    {
        $field_names = $this->connection->getFieldNames('writers');

        $this->assertIsArray($field_names);
        $this->assertCount(3, $field_names);
        $this->assertEquals(['id', 'name', 'birthday'], $field_names);
    }

    /**
     * Test field exists.
     */
    public function testFieldExists()
    {
        $this->assertTrue($this->connection->fieldExists('writers', 'birthday'));
        $this->assertFalse($this->connection->fieldExists('writers', 'unknown field'));
    }

    /**
     * Test drop field.
     */
    public function testDropField()
    {
        $this->assertContains('birthday', $this->connection->getFieldNames('writers'));
        $this->assertCount(3, $this->connection->getFieldNames('writers'));

        $this->connection->dropField('writers', 'birthday');

        $this->assertNotContains('birthday', $this->connection->getFieldNames('writers'));
        $this->assertCount(2, $this->connection->getFieldNames('writers'));
    }

    /**
     * Test safe drop of non-existing field.
     */
    public function testSafeDropNonExistingField()
    {
        $this->assertCount(3, $this->connection->getFieldNames('writers'));
        $this->connection->dropField('writers', 'field that does not exist');
        $this->assertCount(3, $this->connection->getFieldNames('writers'));
    }

    public function testUnsafeDropNonExistingField()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Can't DROP COLUMN `field that does not exist`; check that it exists");

        $this->assertCount(3, $this->connection->getFieldNames('writers'));
        $this->connection->dropField('writers', 'field that does not exist', false);
        $this->assertCount(3, $this->connection->getFieldNames('writers'));
    }
}
