<?php

namespace Pantono\Container\Service\Tests\Mocks;

use Pantono\Contracts\Attributes\ServiceName;

class TestAttributeServiceName
{
    private TestLocateModel $otherService;

    public function __construct(#[ServiceName('TestService')] TestLocateModel $otherService)
    {
        $this->otherService = $otherService;
    }
}
