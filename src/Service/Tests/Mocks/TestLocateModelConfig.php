<?php

namespace Pantono\Container\Service\Tests\Mocks;

class TestLocateModelConfig
{
    private string $configVar;

    public function __construct(string $configVar)
    {

        $this->configVar = $configVar;
    }
}
