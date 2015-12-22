<?php

namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use ActiveCollab\DatabaseConnection\Test\Fixture\Container;
use ActiveCollab\DatabaseConnection\Test\Fixture\WriterWithContainer;
use DateTime;
use Interop\Container\ContainerInterface;

class ContainerPropagatesToObjectTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Container
     */
    private $container;

    /**
     * Set up test environment.
     */
    public function setUp()
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
            'dependency' => 'it works!'
        ]);
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown()
    {
        if ($this->connection->tableExists('writers')) {
            $this->connection->dropTable('writers');
        }

        parent::tearDown();
    }

    /**
     * @throws \ActiveCollab\DatabaseConnection\Exception\Query
     */
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
