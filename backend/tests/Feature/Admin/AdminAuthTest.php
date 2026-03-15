<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

it('returns token for valid admin credentials', function () {
    Redis::shouldReceive('exists')->once()->with('admin:lockout:admin@zeronoshow.com')->andReturn(0);
    Redis::shouldReceive('del')->once()->with('admin:attempts:admin@zeronoshow.com');
    Redis::shouldReceive('del')->once()->with('admin:lockout:admin@zeronoshow.com');

    $admin = Admin::factory()->create([
        'email' => 'admin@zeronoshow.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'admin@zeronoshow.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token', 'admin' => ['id', 'name', 'email']]);
});

it('returns 401 for invalid password', function () {
    Redis::shouldReceive('exists')->once()->with('admin:lockout:admin@zeronoshow.com')->andReturn(0);
    Redis::shouldReceive('incr')->once()->with('admin:attempts:admin@zeronoshow.com')->andReturn(1);
    Redis::shouldReceive('expire')->once()->with('admin:attempts:admin@zeronoshow.com', 900);

    Admin::factory()->create([
        'email' => 'admin@zeronoshow.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'admin@zeronoshow.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

it('locks out after 5 failed attempts via Redis', function () {
    Admin::factory()->create([
        'email' => 'admin@zeronoshow.com',
        'password' => bcrypt('secret123'),
    ]);

    Redis::shouldReceive('exists')
        ->times(6)
        ->with('admin:lockout:admin@zeronoshow.com')
        ->andReturn(0, 0, 0, 0, 0, 1);
    Redis::shouldReceive('incr')
        ->times(5)
        ->with('admin:attempts:admin@zeronoshow.com')
        ->andReturn(1, 2, 3, 4, 5);
    Redis::shouldReceive('expire')->once()->with('admin:attempts:admin@zeronoshow.com', 900);
    Redis::shouldReceive('setex')->once()->with('admin:lockout:admin@zeronoshow.com', 900, 1);
    Redis::shouldReceive('del')->once()->with('admin:attempts:admin@zeronoshow.com');

    // 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@zeronoshow.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    // 6th attempt should be locked out (429)
    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'admin@zeronoshow.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(429)
        ->assertJsonFragment(['message' => 'Trop de tentatives, veuillez reessayer dans 15 minutes.']);
});

it('revokes current token on logout', function () {
    $admin = Admin::factory()->create();

    $token = $admin->createToken('test-admin', ['admin']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/admin/logout');

    $response->assertStatus(204);

    expect($token->accessToken->fresh())->toBeNull();
});

it('returns 401 for unauthenticated admin route', function () {
    $response = $this->postJson('/api/v1/admin/logout');

    $response->assertStatus(401);
});
