<?php

namespace Pantono\Container\Service\Collection;

use Pantono\Container\Service\Model\Service;

class ServiceCollection
{
    /**
     * @var Service[]
     */
    private array $services = [];

    public function addService(Service $service): void
    {
        $this->services[$service->getName()] = $service;
    }

    public function getServiceByName(string $name): ?Service
    {
        return $this->services[$name] ?? null;
    }

    public function getServiceByClass(string $className): ?Service
    {
        foreach ($this->services as $service) {
            if ($service->getClassName() === $className) {
                return $service;
            }
        }
        foreach ($this->services as $service) {
            if (in_array($className, $service->getAliases())) {
                return $service;
            }
        }
        return null;
    }

    /**
     * @return Service[]
     */
    public function getAllServices(): array
    {
        return $this->services;
    }
}
