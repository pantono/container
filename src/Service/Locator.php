<?php

namespace Pantono\Container\Service;

use Pantono\Container\Service\Collection\ServiceCollection;
use Pantono\Container\Service\Exception\ServiceNotRegistered;
use Pantono\Container\Service\Exception\ServiceClassDoesNotExist;
use ReflectionNamedType;
use Pantono\Contracts\Locator\LocatorInterface;
use Pantono\Contracts\Container\ContainerInterface;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Database\Repository\AbstractPdoRepository;
use Pantono\Container\Service\Model\Service;
use Pantono\Contracts\Locator\FactoryInterface;
use Pantono\Database\Connection\ConnectionCollection;
use ReflectionClass;
use Pantono\Container\Container;

class Locator implements LocatorInterface
{
    private Container $container;
    private ServiceCollection $collection;

    public function __construct(Container $container, ServiceCollection $collection)
    {
        $this->container = $container;
        $this->collection = $collection;
    }

    public function lookupRecord(string $className, mixed $id): mixed
    {
        return $this->getContainer()->getHydrator()->lookupRecord($className, $id);
    }

    public function getContainerName(string $dependency): string
    {
        if (str_starts_with($dependency, ':') || str_starts_with($dependency, '@') || str_starts_with($dependency, '%')) {
            return substr($dependency, 1);
        }

        return $dependency;
    }

    public function getServiceByIdentifier(string $identifier): ?Service
    {
        $name = $this->getContainerName($identifier);
        return $this->collection->getServiceByName($name);
    }

    public function loadDependency(string $dependency): mixed
    {
        $containerName = $this->getContainerName($dependency);
        if (!$this->container->has('service_' . $containerName)) {
            $service = $this->collection->getServiceByName($containerName);
            if ($service === null) {
                if (str_starts_with($dependency, '%')) {
                    $locatedDependency = $this->getContainer()[substr($dependency, 1)];
                    if ($locatedDependency) {
                        return $locatedDependency;
                    }
                }
                if (str_starts_with($dependency, ':')) {
                    $locatedDependency = $this->loadRepository(substr($dependency, 1));
                    if ($locatedDependency) {
                        return $locatedDependency;
                    }
                }
                throw new ServiceNotRegistered('Service ' . $dependency . ' does not exist');
            }
            if (!class_exists($service->getClassName())) {
                throw new ServiceClassDoesNotExist('Class ' . $service->getClassName() . ' does not exist for service ' . $dependency);
            }

            $dependencies = [];
            $serviceDependencies = empty($service->getDependencies()) ? $this->getDependenciesForAutoWire($service->getClassName()) : $service->getDependencies();
            foreach ($serviceDependencies as $dependencyName) {
                $locatedDependency = null;
                if (str_starts_with($dependencyName, '$')) {
                    $locatedDependency = $this->container->getConfig()->getConfigForType('config')->getValue(substr($dependencyName, 1));
                } elseif (str_starts_with($dependencyName, '@')) {
                    $locatedDependency = $this->loadDependency(substr($dependencyName, 1));
                } elseif (str_starts_with($dependencyName, ':')) {
                    $locatedDependency = $this->loadRepository(substr($dependencyName, 1));
                } else {
                    $locatedDependency = $this->locateDependencyByClassName($dependencyName);
                }
                if ($locatedDependency === null) {
                    throw new \RuntimeException('Unable to locate dependency ' . $dependencyName);
                }
                $dependencies[] = $locatedDependency;
            }
            $reflectionClass = new ReflectionClass($service->getClassName());
            if (!empty($dependencies)) {
                $generatedClass = $reflectionClass->newInstanceArgs($dependencies);
            } else {
                $className = $service->getClassName();
                $generatedClass = new $className();
            }
            if (in_array(FactoryInterface::class, $reflectionClass->getInterfaceNames())) {
                if (method_exists($generatedClass, 'createInstance')) {
                    $generatedClass = $generatedClass->createInstance();
                }
            }
            $this->container['service_' . $containerName] = $generatedClass;
        }
        return $this->container['service_' . $containerName];
    }

    private function locateDependencyByClassName(string $className): mixed
    {
        // Bit of a hack... need to add the concept of services aliases at some point...
        if ($className === ContainerInterface::class) {
            return $this->container;
        }
        if ($className === SecurityContextInterface::class) {
            return $this->container->getSecurityContext();
        }
        $dep = $this->collection->getServiceByClass($className);
        if ($dep) {
            return $this->loadDependency($dep->getName());
        }
        $repository = $this->loadRepository($className);
        if ($repository) {
            return $repository;
        }
        throw new \Exception('Unable to locate dependency ' . $className);
    }

    /**
     * @deprecated Use load dependency instead
     */
    public function loadService(string $name): mixed
    {
        return $this->loadDependency($name);
    }

    private function loadRepository(string $className): ?AbstractPdoRepository
    {
        if (!class_exists($className)) {
            return null;
        }
        $reflectionClass = new ReflectionClass($className);
        /**
         * @var ConnectionCollection $connectionCollection
         */
        $connectionCollection = $this->container->getService('DatabaseConnectionCollection');
        if (!$reflectionClass->getParentClass()) {
            return null;
        }
        $parents = [];
        $parent = $reflectionClass->getParentClass();
        while ($parent) {
            $parents[] = $parent->getName();
            $parent = $parent->getParentClass();
        }
        if (!in_array(AbstractPdoRepository::class, $parents)) {
            return null;
        }
        $connection = $connectionCollection->getConnectionForParent($reflectionClass->getParentClass()->getName());

        /**
         * @var AbstractPdoRepository $connection
         */
        $connection = $reflectionClass->newInstanceArgs([$connection]);
        return $connection;
    }

    private function getDependenciesForAutoWire(string $className): array
    {
        if (!class_exists($className)) {
            throw new \RuntimeException('Class ' . $className . ' does not exist');
        }
        $class = new ReflectionClass($className);
        $params = [];
        if ($class->getConstructor()) {
            foreach ($class->getConstructor()->getParameters() as $parameter) {
                if ($parameter->getType() instanceof ReflectionNamedType) {
                    $params[] = $parameter->getType()->getName();
                }
            }
        }
        return $params;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getClassAutoWire(string $className): mixed
    {
        if (!class_exists($className)) {
            return null;
        }
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $className();
        }
        $service = $this->collection->getServiceByClass($className);
        $deps = [];
        if ($service) {
            foreach ($service->getDependencies() as $dep) {
                $deps[] = $this->loadDependency($dep);
            }
        } else {
            foreach ($constructor->getParameters() as $index => $parameter) {
                if ($parameter->getType() instanceof ReflectionNamedType) {
                    $dep = $this->locateDependencyByClassName($parameter->getType()->getName());
                    if (!$dep) {
                        throw new \Exception('Unable to locate dependency ' . $parameter->getType()->getName());
                    }
                    $deps[] = $dep;
                } else {
                    throw new \Exception('Cannot instantiate parameter ' . $index . ' on ' . $className . ' due to having no type');
                }
            }
        }


        return $reflection->newInstanceArgs($deps);
    }

    public function loadClass(string $className, array $dependencies): mixed
    {
        if (!class_exists($className)) {
            return null;
        }
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $className();
        }
        $injectedDeps = [];
        foreach ($dependencies as $dependency) {
            $injectedDeps[] = $this->loadDependency($dependency);
        }

        return $reflection->newInstanceArgs($injectedDeps);
    }
}
