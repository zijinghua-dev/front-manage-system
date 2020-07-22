<?php

namespace Zijinghua\Zvoyager\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard;
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
        //mock hash
        $stubHash = $this->createMock(Hash::class);
        $stubHash->method('make')->will($this->returnValue('password'));
        $stubHash->method('check')->will($this->returnValue(true));
        $this->container->singleton(Hash::class, function () use ($stubHash) {
            return $stubHash;
        });

        $request = app(Request::class);
        \Mockery::mock(JWTGuard::class, function ($mock) {
            $mock->shouldReceive('login')->with([])->andReturn('token');
        });
        $user = \Mockery::mock(User::class, function ($mock) {
            $mock->shouldReceive('getJWTCustomClaims')->with([])->andReturn([
                'uuid' => 'aaaaaaaaa',
                'username' => 'test_user',
                'created_at' => "2020-06-22 07:12:01",
                'updated_at' => "2020-06-22 07:12:01"
            ]);
            $mock->shouldReceive('getAttribute')->with('password')->andReturn(Hash::make('password'));
        });
        $jwt = \Mockery::mock(JWT::class, function ($mock) use ($user) {
            $mock->shouldReceive('fromUser')->with($user)->andReturn('token');
            $mock->shouldReceive('setToken')->with('token')->andReturn('token');
        });
        //mock user
        $stub = $this->createMock(User::class);
        $stub->method('search')->will($this->returnValue($user));
        $this->container->singleton(User::class, function () use ($stub) {
            return $stub;
        });
        $provider = app(ClientRestfulUserProvider::class);
        $zguard = new ZGuard($jwt, $provider, $request);
        $response = $zguard->attempt(['username' => 'admin', 'password' => 'password']);
        $this->assertEquals('token', $response);
    }
}