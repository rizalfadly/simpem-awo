<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginByNomorAnggotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_member_can_login_with_nomor_anggota_case_insensitive(): void
    {
        $user = User::factory()->create(['nomor_anggota' => 'AWO-0100']);

        $this->post('/login', ['nomor_anggota' => 'awo-0100', 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login', 'user_id' => $user->id]);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_wrong_password_fails_and_is_audited(): void
    {
        User::factory()->create(['nomor_anggota' => 'AWO-0101']);

        $this->post('/login', ['nomor_anggota' => 'AWO-0101', 'password' => 'salah'])
            ->assertSessionHasErrors('nomor_anggota');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event' => 'login_failed']);
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::factory()->create(['nomor_anggota' => 'AWO-0102', 'is_active' => false]);

        $this->post('/login', ['nomor_anggota' => 'AWO-0102', 'password' => 'password'])
            ->assertSessionHasErrors('nomor_anggota');

        $this->assertGuest();
    }

    public function test_email_is_not_accepted_as_login_identifier(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('nomor_anggota');

        $this->assertGuest();
    }
}
