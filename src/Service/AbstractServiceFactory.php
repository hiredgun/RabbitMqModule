<?php

namespace RabbitMqModule\Service;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class AbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * @var string
     */
    protected $configKey = 'rabbitmq_module';

    /**
     * Determine if we can create a service with name.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return false !== $this->getFactoryMapping($serviceLocator, $requestedName);
    }

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @param string                                       $name
     *
     * @return bool|array
     */
    private function getFactoryMapping(ServiceLocatorInterface $serviceLocator, $name)
    {
        $matches = [];

        $pattern = sprintf('/^%s\.(?P<serviceType>[a-z0-9_]+)\.(?P<serviceName>[a-z0-9_]+)$/', $this->configKey);
        if (!preg_match($pattern, $name, $matches)) {
            return false;
        }
        /** @var array $config */
        $config = $serviceLocator->get('Configuration');
        $serviceType = $matches['serviceType'];
        $serviceName = $matches['serviceName'];

        $moduleConfig = $config[$this->configKey];
        $factoryConfig = $moduleConfig['factories'];

        if (!isset($factoryConfig[$serviceType], $moduleConfig[$serviceType][$serviceName])) {
            return false;
        }

        return [
            'serviceType' => $serviceType,
            'serviceName' => $serviceName,
            'factoryClass' => $factoryConfig[$serviceType],
        ];
    }

    /**
     * Create service with name.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return mixed
     * @throws ServiceNotFoundException
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $mappings = $this->getFactoryMapping($serviceLocator, $requestedName);

        if (!$mappings) {
            throw new ServiceNotFoundException();
        }

        $factoryClass = $mappings['factoryClass'];
        /* @var $factory \RabbitMqModule\Service\AbstractFactory */
        $factory = new $factoryClass($mappings['serviceName']);

        return $factory->createService($serviceLocator);
    }
}
