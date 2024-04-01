<?php

namespace Pantono\Container\Service\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Container\Service\Collection\ServiceCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Pantono\Container\Service\Model\Service;
use Pantono\Container\Service\Tests\Mocks\TestLocateModel;
use Pantono\Container\Service\Locator;
use Pantono\Container\Service\Tests\Mocks\TestLocateModelConfig;
use Pantono\Database\Connection\ConnectionCollection;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Container\Service\Tests\Mocks\TestRepository;
use Pantono\Database\Repository\MysqlRepository;
use Pantono\Container\Service\Tests\Mocks\TestRepositoryInjected;
use Pantono\Container\Service\Tests\Mocks\TestLinkedService;
use Pantono\Container\Container;
use Pantono\Contracts\Config\FileInterface;
use Pantono\Contracts\Config\ConfigInterface;

class LocatorTest extends TestCase
{
    private Container $container;
    private ServiceCollection|MockObject $serviceCollection;

    public function setUp(): void
    {
        $this->container = new Container();
        $this->serviceCollection = $this->getMockBuilder(ServiceCollection::class)->disableOriginalConstructor()->getMock();
    }

    public function testServiceLocate()
    {
        $mockService = new Service('test', TestLocateModel::class, []);
        $this->serviceCollection->expects($this->once())
            ->method('getServiceByName')
            ->with('test')
            ->willReturn($mockService);

        $this->assertEquals(new TestLocateModel(), $this->getLocator()->loadDependency('test'));
    }

    public function testServiceLocateConfigVar()
    {
        $mockService = new Service('test', TestLocateModelConfig::class, ['$config.var']);
        $this->serviceCollection->expects($this->once())
            ->method('getServiceByName')
            ->with('test')
            ->willReturn($mockService);

        $fileMock = $this->getMockBuilder(FileInterface::class)->disableOriginalConstructor()->getMock();
        $fileMock->expects($this->once())->method('getValue')->with('config.var')->willReturn('test');
        $configMock = $this->getMockBuilder(ConfigInterface::class)->disableOriginalConstructor()->getMock();
        $configMock->expects($this->once())->method('getConfigForType')->with('config')->willReturn($fileMock);
        $this->container['config'] = $configMock;

        $this->assertEquals(new TestLocateModelConfig('test'), $this->getLocator()->loadDependency('test'));
    }

    public function testServiceLocatorRepository()
    {
        $mockService = new Service('test', TestRepositoryInjected::class, [':' . TestRepository::class]);
        $this->serviceCollection->expects($this->once())
            ->method('getServiceByName')
            ->with('test')
            ->willReturn($mockService);

        $dbMock = $this->getMockBuilder(MysqlDb::class)->disableOriginalConstructor()->getMock();
        $connectionCollection = $this->getMockBuilder(ConnectionCollection::class)->disableOriginalConstructor()->getMock();
        $connectionCollection->expects($this->once())
            ->method('getConnectionForParent')
            ->with(MysqlRepository::class)
            ->willReturn($dbMock);
        $this->container['service_DatabaseConnectionCollection'] = $connectionCollection;

        $this->assertEquals(new TestRepositoryInjected(new TestRepository($dbMock)), $this->getLocator()->loadDependency('test'));
    }

    public function testServiceLocatorService()
    {
        $this->serviceCollection->expects($this->exactly(2))
            ->method('getServiceByName')
            ->willReturnOnConsecutiveCalls(
                new Service('test', TestLinkedService::class, ['@test1']),
                new Service('test2', TestLocateModel::class, [])
            );

        $expected = new TestLinkedService(new TestLocateModel());
        $this->assertEquals($expected, $this->getLocator()->loadDependency('test'));
    }

    public function testServiceLocatorAutoWireRepository()
    {
        $mockService = new Service('test', TestRepositoryInjected::class, []);
        $this->serviceCollection->expects($this->once())
            ->method('getServiceByName')
            ->with('test')
            ->willReturn($mockService);

        $dbMock = $this->getMockBuilder(MysqlDb::class)->disableOriginalConstructor()->getMock();
        $connectionCollection = $this->getMockBuilder(ConnectionCollection::class)->disableOriginalConstructor()->getMock();
        $connectionCollection->expects($this->once())
            ->method('getConnectionForParent')
            ->with(MysqlRepository::class)
            ->willReturn($dbMock);
        $this->container['service_DatabaseConnectionCollection'] = $connectionCollection;

        $this->assertEquals(new TestRepositoryInjected(new TestRepository($dbMock)), $this->getLocator()->loadDependency('test'));
    }

    private function getLocator(): Locator
    {
        return new Locator($this->container, $this->serviceCollection);
    }
}
