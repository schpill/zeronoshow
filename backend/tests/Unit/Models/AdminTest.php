<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses has uuids', function () {
    $admin = Admin::factory()->create();

    expect($admin->getKey())->toBeString()
        ->and(strlen($admin->getKey()))->toBe(36);
});

it('casts password to hashed', function () {
    $admin = Admin::factory()->create(['password' => 'secret123']);

    expect($admin->getRawOriginal('password'))->not->toBe('secret123')
        ->and(password_verify('secret123', $admin->getRawOriginal('password')))->toBeTrue();
});

it('can check admin ability via tokenCan', function () {
    $admin = Admin::factory()->create();
    $token = $admin->createToken('test-token', ['admin']);

    expect($token->accessToken->can('admin'))->toBeTrue()
        ->and($token->accessToken->can('other'))->toBeFalse();
});
