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

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Test\Fixture\Container;
use ActiveCollab\DatabaseConnection\Test\Fixture\WriterWithContainer;
use DateTime;
use Psr\Container\ContainerInterface;

class ContainerPropagatesToObjectTest extends TestCase
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new Connection\MysqliConnection($this->link);

        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateTime('1828-09-09'), 'Alexander Pushkin', new DateTime('1799-06-06'), 'Fyodor Dostoyevsky', new DateTime('1821-11-11'));

        $this->container = new Container([
            'dependency' => 'it works!',
        ]);
    }

    public function tearDown(): void
    {
        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        parent::tearDown();
    }

    public function testExceptionWhenLoadingByObjectClassAndClassNameIsEmpty()
    {
        /** @var WriterWithContainer[] $result */
        $result = $this->connection->advancedExecute('SELECT * FROM `writers` ORDER BY `id`', null, ConnectionInterface::LOAD_ALL_ROWS, ConnectionInterface::RETURN_OBJECT_BY_CLASS, WriterWithContainer::class, null, $this->container);

        $this->assertCount(3, $result);

        foreach ($result as $writer) {
            $this->assertInstanceOf(WriterWithContainer::class, $writer);
            $this->assertInstanceOf(ContainerInterface::class, $writer->getContainer());
            $this->assertEquals('it works!', $writer->dependency);
        }
    }
}
