<?php

namespace Pantono\Container\Traits;

use Pantono\Container\StaticContainer;
use Symfony\Component\HttpFoundation\Session\Session;

trait SessionAware
{
    public function getSession(): Session
    {
        return StaticContainer::getSession();
    }
}
