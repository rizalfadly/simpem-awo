<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->assignRole(User::SUPER_ADMIN));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $this->actingAs(User::factory()->create())->get('/users')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/audit-logs')->assertForbidden();
    }

    public function test_user_with_permission_can_view_users(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('users.view');

        $this->actingAs($user)->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Users/Index'));
    }

    public function test_super_admin_can_open_every_module(): void
    {
        $this->actingAs($this->superAdmin());

        foreach (['/dashboard', '/users', '/roles', '/audit-logs'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_dashboard_only_exposes_widgets_the_user_may_see(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats', [])
                ->where('recent', null));
    }

    public function test_cannot_deactivate_own_account(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->patch("/users/{$admin->id}/toggle-active")->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }




    public function test_non_super_admin_cannot_touch_a_super_admin_account(): void
    {
        $target = $this->superAdmin();
        $actor = User::factory()->create();
        $actor->givePermissionTo(['users.update', 'users.delete']);

        $this->actingAs($actor)->get("/users/{$target->id}/edit")->assertForbidden();
        $this->actingAs($actor)->delete("/users/{$target->id}")->assertForbidden();
    }

    public function test_temporary_password_forces_profile_redirect(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/profile');
        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_changing_password_clears_the_forced_flag(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->put('/password', [
            'current_password'      => 'password',
            'password'              => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertRedirect('/dashboard');

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_role_in_use_cannot_be_deleted(): void
    {
        $member = User::factory()->create();
        $member->assignRole('Anggota');
        $role = \Spatie\Permission\Models\Role::findByName('Anggota');

        $this->actingAs($this->superAdmin())->delete("/roles/{$role->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'Anggota']);
    }

    public function test_super_admin_role_is_protected(): void
    {
        $role = \Spatie\Permission\Models\Role::findByName(User::SUPER_ADMIN);

        $this->actingAs($this->superAdmin())->get("/roles/{$role->id}/edit")->assertForbidden();
        $this->actingAs($this->superAdmin())->delete("/roles/{$role->id}")->assertForbidden();
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo('users.update');
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->put("/users/{$target->id}", ['roles' => [User::SUPER_ADMIN]])
            ->assertSessionHasErrors('roles.0');

        $this->assertFalse($target->fresh()->hasRole(User::SUPER_ADMIN));
    }

    public function test_accounts_can_no_longer_be_created_from_users_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/users', ['nomor_anggota' => 'AWO-0300', 'name' => 'X'])
            ->assertStatus(405);
    }
}
