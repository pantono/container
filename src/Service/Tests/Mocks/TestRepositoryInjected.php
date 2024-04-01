<?php

namespace Pantono\Container\Service\Tests\Mocks;

class TestRepositoryInjected
{
    private TestRepository $repository;

    public function __construct(TestRepository $repository)
    {
        $this->repository = $repository;
    }
}
