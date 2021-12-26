<?php

/*
 * This file is part of the Active Collab DatabaseConnection.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection\MysqliConnection;
use ActiveCollab\DatabaseConnection\Exception\QueryException;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class ForeignKeysTest extends TestCase
{
    /**
     * @var MysqliConnection
     */
    private $connection;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new MysqliConnection($this->link);

        $this->connection->turnOffForeignKeyChecks();

        $this->connection->execute('DROP TABLE IF EXISTS `periods`');
        $this->connection->execute("CREATE TABLE `periods` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->connection->execute('DROP TABLE IF EXISTS `topics`');
        $this->connection->execute("CREATE TABLE `topics` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->connection->execute('DROP TABLE IF EXISTS `writers`');
        $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `period_id` int(11) NOT NULL DEFAULT '0',
            `topic_id` int(11) NOT NULL DEFAULT '0',
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`),
            KEY (`name`),
            CONSTRAINT `writer_period` FOREIGN KEY (`period_id`) REFERENCES `periods`(`id`),
            CONSTRAINT `writer_topic` FOREIGN KEY (`topic_id`) REFERENCES `topics`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->connection->turnOnForeignKeyChecks();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        $this->connection->turnOffForeignKeyChecks();

        $this->connection->execute('DROP TABLE IF EXISTS `writers`');
        $this->connection->execute('DROP TABLE IF EXISTS `periods`');
        $this->connection->execute('DROP TABLE IF EXISTS `topics`');

        $this->connection->turnOnForeignKeyChecks();

        $this->assertEquals([], $this->connection->getTableNames());

        parent::tearDown();
    }

    /**
     * Test if FK checks toggler works.
     */
    public function testFkChecksOnOff()
    {
        $this->assertTrue($this->connection->areForeignKeyChecksOn());
        $this->connection->turnOffForeignKeyChecks();
        $this->assertFalse($this->connection->areForeignKeyChecksOn());
        $this->connection->turnOnForeignKeyChecks();
        $this->assertTrue($this->connection->areForeignKeyChecksOn());
    }

    /**
     * Test get FK names.
     */
    public function testFkNames()
    {
        $fk_names = $this->connection->getForeignKeyNames('writers');

        $this->assertIsArray($fk_names);
        $this->assertCount(2, $fk_names);

        $this->assertContains('writer_period', $fk_names);
        $this->assertContains('writer_topic', $fk_names);
    }

    /**
     * Test if FK exists.
     */
    public function testFkExists()
    {
        $this->assertTrue($this->connection->foreignKeyExists('writers', 'writer_period'));
        $this->assertFalse($this->connection->foreignKeyExists('writers', 'unknown FK'));
    }

    /**
     * Test drop FK.
     */
    public function testDropFk()
    {
        $this->assertContains('writer_period', $this->connection->getForeignKeyNames('writers'));
        $this->assertCount(2, $this->connection->getForeignKeyNames('writers'));

        $this->connection->dropForeignKey('writers', 'writer_period');

        $this->assertNotContains('writer_period', $this->connection->getForeignKeyNames('writers'));
        $this->assertCount(1, $this->connection->getForeignKeyNames('writers'));
    }

    /**
     * Test safe drop of non-existing FK.
     */
    public function testSafeDropNonExistingFk()
    {
        $this->assertCount(2, $this->connection->getForeignKeyNames('writers'));
        $this->connection->dropForeignKey('writers', 'FK that does not exist');
        $this->assertCount(2, $this->connection->getForeignKeyNames('writers'));
    }

    public function testUnsafeDropNonExistingFk()
    {
        $this->expectException(QueryException::class);

        $this->assertCount(2, $this->connection->getForeignKeyNames('writers'));
        $this->connection->dropForeignKey('writers', 'FK that does not exist', false);
        $this->assertCount(2, $this->connection->getForeignKeyNames('writers'));
    }
}
