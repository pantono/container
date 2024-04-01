<?php

namespace Pantono\Container\Traits;

use Pantono\Container\Container;
use Pantono\Container\StaticContainer;

trait ContainerAware
{
    public function getContainer(): Container
    {
        return StaticContainer::getContainer();
    }
}
