<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
})->group('core');

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

// pats dzēstu kontu var atjaunot, pieslēdzoties ar savu paroli; skolotāja izņemtu – nevar
test('self deleted account can be restored at sign in', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->post(route('login.restore'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');
    $this->assertSoftDeleted($user);

    $this->post(route('login.restore'), ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
    $this->assertNotSoftDeleted($user);
});

test('account removed by a teacher cannot be restored at sign in', function () {
    $teacher = User::factory()->create();
    $group = \App\Models\Group::create(['name' => 'Folkloras kopa', 'admin_id' => $teacher->id]);
    $user = User::factory()->create(['deactivated_with_group_id' => $group->id]);
    $user->delete();

    $this->post(route('login.restore'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertSoftDeleted($user);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    // pēc iziešanas lietotne ved uz pieslēgšanās lapu, nevis sākumlapu
    $this->assertGuest();
    $response->assertRedirect('/login');
});
