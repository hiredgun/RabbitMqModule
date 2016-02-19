<?php

namespace RabbitMqModule\Service;

use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceManager;

class AbstractServiceFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->serviceManager = new ServiceManager();
        $this->serviceManager->setService(
            'Configuration',
            [
                'rabbitmq_module' => [
                    'connection' => [
                        'default' => [],
                    ],
                    'producer' => [
                        'foo' => [
                            'exchange' => [],
                        ],
                    ],
                    'foo' => [
                        'bar' => [

                        ],
                    ],
                    'factories' => [
                        'foo' => 'fooFactory',
                        'producer' => 'RabbitMqModule\\Service\\ServiceFactoryMock',
                    ],
                ],
            ]
        );
    }

    public function testCanCreateServiceWithName()
    {
        $sm = $this->serviceManager;
        $factory = new AbstractServiceFactory();
        static::assertTrue($factory->canCreateServiceWithName($sm, 'rabbitmq_module.foo.bar', 'rabbitmq_module.foo.bar'));
        static::assertFalse($factory->canCreateServiceWithName($sm, 'rabbitmq_module.foo.bar', 'rabbitmq_module.foo.bar2'));
    }

    public function testCreateServiceWithName()
    {
        $connection = static::getMockBuilder('PhpAmqpLib\\Connection\\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sm = $this->serviceManager;
        $sm->setService('rabbitmq_module.connection.default', $connection);
        $factory = new AbstractServiceFactory();
        static::assertTrue(
            $factory->createServiceWithName($sm, 'rabbitmq_module.producer.foo', 'rabbitmq_module.producer.foo')
        );
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\ServiceNotFoundException
     */
    public function testCreateServiceUnknown()
    {
        $sm = $this->serviceManager;
        $factory = new AbstractServiceFactory();
        static::assertTrue(
            $factory->createServiceWithName($sm, 'rabbitmq_module.unknown-key.foo', 'rabbitmq_module.unknown-key.foo')
        );
    }
}
