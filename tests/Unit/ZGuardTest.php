<?php

namespace Zijinghua\Zvoyager\Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;

use Tymon\JWTAuth\JWT;
use Zijinghua\Zvoyager\App\Guards\ZGuard;
use Zijinghua\Zvoyager\App\Models\User;
use Zijinghua\Zvoyager\App\Providers\ClientRestfulUserProvider;


class ZGuardTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAttempt()
    {
        $jwt = \Mockery::mock(JWT::class);
        $request = app(Request::class);
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
        $provider = app(ClientRestfulUserProvider::class);
        $zguard = new ZGuard($jwt, $provider, $request);
        $response = $zguard->attempt(['username' => 'admin', 'password' => '123123']);
        $this->assertEquals('aaaaaaaaa', $response['uuid']);
    }
}