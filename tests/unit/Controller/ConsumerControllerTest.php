<?php

namespace RabbitMqModule\Controller;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class ConsumerControllerTest extends AbstractConsoleControllerTestCase
{

    protected function setUp()
    {
        $config = include __DIR__.'/../../TestConfiguration.php.dist';
        $this->setApplicationConfig($config);
        parent::setUp();
    }

    public function testDispatchWithTestConsumer()
    {
        $consumer = static::getMock('RabbitMqModule\Consumer', array('consume'), array(), '', false);
        $consumer
            ->expects(static::once())
            ->method('consume');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('rabbitmq_module.consumer.foo', $consumer);

        ob_start();
        $this->dispatch('rabbitmq-module consumer foo');
        ob_end_clean();

        $this->assertResponseStatusCode(0);
    }

    public function testDispatchWithInvalidTestConsumer()
    {
        ob_start();
        $this->dispatch('rabbitmq-module consumer foo');
        $output = ob_get_clean();

        static::assertRegExp('/No consumer with name "foo" found/', $output);

        $this->assertResponseStatusCode(1);
    }

    public function testStopConsumerController()
    {
        $consumer = static::getMock('RabbitMqModule\Consumer', ['forceStopConsumer', 'stopConsuming'], [], '', false);

        $consumer->expects(static::once())
            ->method('forceStopConsumer');

        $consumer->expects(static::once())
            ->method('stopConsuming');

        $stub = $this->getMockBuilder('RabbitMqModule\\Controller\\ConsumerController')
            ->setMethods(array('callExit'))
            ->getMock();

        $stub->expects(static::once())
            ->method('callExit');

        /** @var \RabbitMqModule\Consumer $consumer */
        /** @var ConsumerController $controller */
        $controller = $stub;
        $controller->setConsumer($consumer);

        $controller->stopConsumer();
    }

    public function testDispatchWithoutSignals()
    {
        $consumer = static::getMock('RabbitMqModule\Consumer', array('consume'), array(), '', false);
        $consumer
            ->expects(static::once())
            ->method('consume');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('rabbitmq_module.consumer.foo', $consumer);

        ob_start();
        $this->dispatch('rabbitmq-module consumer foo --without-signals');
        ob_end_clean();

        static::assertTrue(defined('AMQP_WITHOUT_SIGNALS'));

        $this->assertResponseStatusCode(0);
    }

    public function testListConsumersWithNoConsumers()
    {
        ob_start();
        $this->dispatch('rabbitmq-module list consumers');
        ob_end_clean();

        $this->assertConsoleOutputContains('No consumers defined!');

        $this->assertResponseStatusCode(0);
    }

    public function testListConsumersWithNoConfigKey()
    {
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        /** @var array $configuration */
        $configuration = $serviceManager->get('Configuration');
        unset($configuration['rabbitmq_module']);
        $serviceManager->setService('Configuration', $configuration);

        ob_start();
        $this->dispatch('rabbitmq-module list consumers');
        ob_end_clean();

        $this->assertConsoleOutputContains('No \'rabbitmq_module.consumer\' configuration key found!');

        $this->assertResponseStatusCode(0);
    }

    public function testListConsumers()
    {
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        /** @var array $configuration */
        $configuration = $serviceManager->get('Configuration');
        $configuration['rabbitmq_module']['consumer'] = [
            'consumer_key1' => [],
            'consumer_key2' => ['description' => 'foo description']
        ];
        $serviceManager->setService('Configuration', $configuration);

        ob_start();
        $this->dispatch('rabbitmq-module list consumers');
        $content = ob_get_contents();
        ob_end_clean();


        static::assertTrue(false !== strpos($content, 'consumer_key1'));
        static::assertTrue(false !== strpos($content, 'consumer_key2'));
        static::assertTrue(false !== strpos($content, 'foo description'));

        $this->assertResponseStatusCode(0);
    }
}
