<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

// reģistrējas skolotājs: konts ar skolotāja lomu un viņa pirmā grupa (ar iebūvētajiem komplektiem)
test('new users can register', function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'member']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'group_name' => 'Drama Club',
        'password' => 'Jauna-Parole-123',
        'password_confirmation' => 'Jauna-Parole-123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->adminGroups()->pluck('name')->all())->toBe(['Drama Club']);
    expect($user->adminGroups()->first()->costumeSets()->pluck('name')->all())->toBe(['Girls', 'Boys']);
})->group('core');
