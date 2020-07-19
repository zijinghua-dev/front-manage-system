<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Illuminate\Container\Container;
use Zijinghua\Zvoyager\App\Constracts\Repositories\UserInterface as UserRepositoryInterface;
use Zijinghua\Zvoyager\App\Constracts\Services\UserInterface as UserServiceInterface;
use Zijinghua\Zvoyager\App\Repositories\UserRepository;
use Zijinghua\Zvoyager\App\Services\UserService;

abstract class TestCase extends BaseTestCase
{
    protected $container;

    protected function setUp() : void
    {
        parent::setUp();
        $this->container = Container::getInstance();
        $this->container->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->container->bind(UserServiceInterface::class, UserService::class);
    }
}
