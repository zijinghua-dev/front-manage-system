<?php

namespace Zijinghua\Zvoyager\Tests\Unit;

use Tests\TestCase;
use Zijinghua\Zvoyager\App\Constracts\Services\UserInterface as UserServiceInterface;
use Zijinghua\Zvoyager\App\Models\User;
use Zijinghua\Zvoyager\App\Providers\ClientRestfulUserProvider;

class ClientRestFullUserProviderTest extends TestCase
{
    public function testRetrieveByCredentials()
    {
        //mock user
        $stub = $this->createMock(User::class);
        $stub->method('search')->will($this->returnValue([
            'uuid' => 'aaaaaaaaa',
            'username' => 'test_user',
            'created_at' => "2020-06-22 07:12:01",
            'updated_at' => "2020-06-22 07:12:01"
        ]));
        $this->container->singleton(User::class, function () use ($stub) {
            return $stub;
        });

        $provider = new ClientRestfulUserProvider(app(UserServiceInterface::class));
        $response = $provider->retrieveByCredentials(['username' => 'admin', 'password' => '123123']);
        $this->assertEquals('aaaaaaaaa', $response['uuid']);
    }
}
