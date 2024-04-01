<?php

namespace Pantono\Container\Service\Model;

class Service
{
    private string $name;
    private string $className;
    private array $dependencies;
    private array $aliases;

    public function __construct(string $name, string $className, array $dependencies = [], array $aliases = [])
    {
        $this->name = $name;
        $this->className = $className;
        $this->dependencies = $dependencies;
        $this->aliases = $aliases;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function setAliases(array $aliases): void
    {
        $this->aliases = $aliases;
    }
}
