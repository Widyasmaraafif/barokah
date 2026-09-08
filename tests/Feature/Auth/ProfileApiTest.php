<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guest cannot access profile endpoints', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->putJson('/api/v1/me', ['name' => 'Nobody'])->assertUnauthorized();
    $this->putJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertUnauthorized();
});

test('authenticated buyer can view own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.is_admin', false)
        ->assertJsonPath('data.is_seller', false);
});

test('authenticated buyer can update own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/v1/me', [
        'name' => 'Ahmad Buyer',
        'email' => $user->email,
        'phone' => '0123456789',
        'address' => '1 Jalan Merdeka',
        'state' => 'Selangor',
        'post_code' => '40000',
    ])->assertOk()
        ->assertJsonPath('data.name', 'Ahmad Buyer')
        ->assertJsonPath('data.phone', '0123456789');

    $user->refresh();

    expect($user->name)->toBe('Ahmad Buyer');
    expect($user->email_verified_at)->not->toBeNull();
});

test('changing profile email resets verification', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/v1/me', [
        'name' => $user->name,
        'email' => 'new-email@example.com',
    ])->assertOk();

    expect($user->refresh()->email_verified_at)->toBeNull();
});

test('profile update validates email uniqueness', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/v1/me', [
        'name' => $user->name,
        'email' => 'taken@example.com',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('profile update rejects unknown state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/v1/me', [
        'name' => $user->name,
        'email' => $user->email,
        'state' => 'Atlantis',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('state');
});

test('authenticated buyer can change password with current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertNoContent();

    expect(Hash::check('new-secure-password', $user->refresh()->password))->toBeTrue();
});

test('password change rejects wrong current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/v1/me/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');
});
