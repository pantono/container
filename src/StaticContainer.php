<?php

namespace Pantono\Container;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Contracts\Locator\LocatorInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class StaticContainer
{
    private static ?Container $container = null;
    private static ?EventDispatcher $dispatcher = null;
    private static ?LocatorInterface $locator = null;
    private static ?Session $session = null;

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    public static function getContainer(): Container
    {
        if (self::$container === null) {
            throw new \RuntimeException('Container instance not available');
        }
        return self::$container;
    }

    public static function setEventDispatcher(EventDispatcher $dispatcher): void
    {
        self::$dispatcher = $dispatcher;
    }

    public static function getEventDispatcher(): EventDispatcher
    {
        if (self::$dispatcher === null) {
            throw new \RuntimeException('Event dispatcher instance not set');
        }

        return self::$dispatcher;
    }

    public static function setLocator(LocatorInterface $locator): void
    {
        self::$locator = $locator;
    }

    public static function getLocator(): LocatorInterface
    {
        if (self::$locator === null) {
            throw new \RuntimeException('Locator not set');
        }

        return self::$locator;
    }

    public static function getSession(): Session
    {
        if (self::$session === null) {
            throw new \RuntimeException('Session not set');
        }
        return self::$session;
    }

    public static function setSession(Session $session): void
    {
        self::$session = $session;
    }
}
