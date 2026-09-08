<?php

use App\Models\User;

function adminEditor(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('admin can update user profile fields', function () {
    $admin = adminEditor();
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($admin)->putJson("/api/v1/admin/users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => $user->email,
        'phone' => '0123456789',
        'address' => 'No. 1, Jalan Test',
        'state' => 'Selangor',
        'post_code' => '46000',
        'is_admin' => false,
        'is_active_as_seller' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.phone', '0123456789')
        ->assertJsonPath('data.state', 'Selangor');

    expect($user->refresh())
        ->address->toBe('No. 1, Jalan Test')
        ->post_code->toBe('46000');
});

test('admin user email change resets verification', function () {
    $admin = adminEditor();
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($admin)->putJson("/api/v1/admin/users/{$user->id}", [
        'name' => $user->name,
        'email' => 'new-email@example.com',
    ])->assertOk();

    expect($user->refresh())
        ->email->toBe('new-email@example.com')
        ->email_verified_at->toBeNull();
});

test('admin user update rejects unknown state and duplicate email', function () {
    $admin = adminEditor();
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($admin)->putJson("/api/v1/admin/users/{$user->id}", [
        'state' => 'Atlantis',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('state');

    $this->actingAs($admin)->putJson("/api/v1/admin/users/{$user->id}", [
        'email' => $other->email,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('buyers cannot update users', function () {
    $buyer = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($buyer)->putJson("/api/v1/admin/users/{$user->id}", [
        'name' => 'Hacker',
    ])->assertForbidden();
});
