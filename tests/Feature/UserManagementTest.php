<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)->get('/internal/users')->assertForbidden();
        $this->actingAs($staff)->get('/internal/users/create')->assertForbidden();
    }

    public function test_admin_can_list_and_create_staff_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get('/internal/users')
            ->assertOk()
            ->assertSee('Pengguna');

        $this->actingAs($admin)
            ->post('/internal/users', [
                'name' => 'Staff Baru',
                'username' => 'staff1',
                'role' => User::ROLE_STAFF,
                'password' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'staff1',
            'role' => User::ROLE_STAFF,
        ]);

        $this->actingAs($admin)
            ->from('/internal/users/create')
            ->post('/internal/users', [
                'name' => 'Admin Baru',
                'username' => 'admin2',
                'role' => User::ROLE_ADMIN,
                'password' => 'password123',
            ])
            ->assertRedirect('/internal/users/create')
            ->assertSessionHasErrors(['role']);

        $this->assertDatabaseMissing('users', [
            'username' => 'admin2',
        ]);
    }

    public function test_admin_cannot_edit_non_staff_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $targetAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get('/internal/users/' . $targetAdmin->id . '/edit')
            ->assertForbidden();
    }

    public function test_super_admin_can_create_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)
            ->post('/internal/users', [
                'name' => 'Admin Baru',
                'username' => 'adminnew',
                'role' => User::ROLE_ADMIN,
                'password' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'adminnew',
            'role' => User::ROLE_ADMIN,
        ]);
    }
}


