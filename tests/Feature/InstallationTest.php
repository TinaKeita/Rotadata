<?php

namespace Tests\Feature;

use App\Models\Costume;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// tīra instalācija: viena dokumentēta komanda dod lomas, publisku attēlu krātuvi un strādājošu reģistrāciju
class InstallationTest extends TestCase
{
    use RefreshDatabase;

    // tikai noklusējuma seeder (kā "composer setup") – lomas netiek veidotas ar roku
    #[\PHPUnit\Framework\Attributes\Group('core')]
    public function test_fresh_database_has_roles_and_registration_works(): void
    {
        $this->seed();

        $this->assertTrue(Role::where('name', 'admin')->exists());
        $this->assertTrue(Role::where('name', 'member')->exists());

        $this->post('/register', [
            'name' => 'Ilze Bērziņa',
            'email' => 'ilze@example.com',
            'group_name' => 'Folkloras kopa',
            'password' => 'Jauna-Parole-123',
            'password_confirmation' => 'Jauna-Parole-123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'ilze@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertSame(['Folkloras kopa'], $user->adminGroups()->pluck('name')->all());
    }

    // ja reģistrācija pusceļā neizdodas (šeit – nav lomu), konts netiek izveidots un e-pastu var izmantot atkārtoti
    public function test_failed_registration_leaves_no_half_created_account(): void
    {
        $this->withoutExceptionHandling();

        try {
            $this->post('/register', [
                'name' => 'Ilze Bērziņa',
                'email' => 'ilze@example.com',
                'group_name' => 'Folkloras kopa',
                'password' => 'Jauna-Parole-123',
                'password_confirmation' => 'Jauna-Parole-123',
            ]);
            $this->fail('Registration without roles should fail.');
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
            // sagaidāms
        }

        $this->assertDatabaseMissing('users', ['email' => 'ilze@example.com']);
        $this->assertSame(0, Group::count());
        $this->assertGuest();
    }

    // setup izveido storage saiti, un augšupielādētā tērpa attēla adrese ved caur to
    public function test_uploaded_costume_image_is_served_from_public_storage(): void
    {
        $setup = json_decode(file_get_contents(base_path('composer.json')), true)['scripts']['setup'];
        $this->assertContains('@php artisan storage:link', $setup);
        $this->assertSame(storage_path('app/public'), config('filesystems.links')[public_path('storage')] ?? null);

        Storage::fake('public');
        Role::create(['name' => 'admin']);
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        Group::create(['name' => 'Folkloras kopa', 'admin_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('admin.costumes.store'), [
            'name' => 'Krekls',
            'quantity' => 1,
            'image' => UploadedFile::fake()->image('krekls.jpg'),
        ])->assertSessionHasNoErrors();

        $costume = Costume::where('name', 'Krekls')->firstOrFail();
        Storage::disk('public')->assertExists($costume->image);
        $this->assertStringContainsString('/storage/costumes/', $costume->imageUrl());
    }
}
