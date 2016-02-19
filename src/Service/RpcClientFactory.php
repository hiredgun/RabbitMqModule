<?php

namespace RabbitMqModule\Service;

use PhpAmqpLib\Connection\AbstractConnection;
use RabbitMqModule\Producer;
use RabbitMqModule\RpcClient;
use Zend\ServiceManager\ServiceLocatorInterface;
use RabbitMqModule\Options\RpcClient as Options;
use InvalidArgumentException;

class RpcClientFactory extends AbstractFactory
{
    /**
     * Get the class name of the options associated with this factory.
     *
     * @return string
     */
    public function getOptionsClass()
    {
        return Options::class;
    }

    /**
     * Create service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var $options Options */
        $options = $this->getOptions($serviceLocator, 'rpc_client');

        return $this->createClient($serviceLocator, $options);
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param Options                 $options
     *
     * @return Producer
     *
     * @throws InvalidArgumentException
     */
    protected function createClient(ServiceLocatorInterface $serviceLocator, Options $options)
    {
        /** @var AbstractConnection $connection */
        $connection = $serviceLocator->get(sprintf('%s.connection.%s', $this->configKey, $options->getConnection()));
        $producer = new RpcClient($connection);
        $producer->setSerializer($options->getSerializer());

        return $producer;
    }
}
