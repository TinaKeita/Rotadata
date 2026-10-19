<?php

use App\Models\User;
use App\Notifications\ResetPasswordLinkNotification as ResetPassword;
use Illuminate\Support\Facades\Notification;

// saites pieprasīšanu un paroles nomaiņu pārbauda MemberPasswordResetTest; šeit – tikai saites atvērtā lapa
test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});
