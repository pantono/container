<?php

namespace Pantono\Container\Service\Tests\Mocks;

class TestLinkedService
{
    private TestLocateModel $otherService;

    public function __construct(TestLocateModel $otherService)
    {

        $this->otherService = $otherService;
    }
}
