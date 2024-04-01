<?php

namespace Pantono\Container;

use Pimple\Container as PimpleContainer;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Pantono\Contracts\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Locator\LocatorInterface;
use Pantono\Container\Service\Locator;
use Pantono\Container\Service\Collection\ServiceCollection;
use Pantono\Container\Service\Model\Service;
use Pantono\Hydrator\Hydrator;
use Pantono\Contracts\Config\ConfigInterface;

class Container extends PimpleContainer implements ContainerInterface, PsrContainerInterface
{
    public function get(string $id)
    {
        return $this[$id];
    }

    public function has(string $id): bool
    {
        return isset($this[$id]);
    }

    public function getConfig(): ConfigInterface
    {
        if ($this->has('config') === false) {
            throw new \RuntimeException('Config not set');
        }
        return $this['config'];
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->getService('EventDispatcher');
    }

    public function getSecurityContext(): ParameterBag
    {
        return $this->getService('SecurityContext');
    }

    public function getLocator(): LocatorInterface|Locator
    {
        return $this->getService('ServiceLocator');
    }

    public function getHydrator(): Hydrator
    {
        if ($this->has('service_Hydrator') === false) {
            throw new \RuntimeException('Hydrator not set');
        }
        return $this['service_Hydrator'];
    }

    public function getServiceCollection(): ServiceCollection
    {
        return $this->getService('ServiceCollection');
    }

    public function getService(string $name): mixed
    {
        $key = 'service_' . $name;
        if ($this->has($key) === false) {
            throw new \RuntimeException('Service ' . $name . ' not set');
        }
        return $this[$key];
    }

    /**
     * @param string $name
     * @param class-string|object $service
     * @param array $aliases
     * @return void
     */
    public function addService(string $name, mixed $service, array $aliases = []): void
    {
        $key = 'service_' . $name;
        if ($this->has($key)) {
            return;
        }
        $this[$key] = $service;
        if (is_object($service)) {
            $className = get_class($service);
        } else {
            $className = $service;
        }
        $service = new Service($name, $className, [], $aliases);
        $this->getServiceCollection()->addService($service);
    }

    public function hasService(string $name): bool
    {
        $key = 'service_' . $name;
        return $this->has($key);
    }
}
