<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@futurelegacyschool.edu',
            'password' => 'CorrectHorse123!',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@futurelegacyschool.edu',
            'password' => 'CorrectHorse123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'admin@futurelegacyschool.edu',
            'password' => 'CorrectHorse123!',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@futurelegacyschool.edu',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'inactive@futurelegacyschool.edu',
            'password' => 'CorrectHorse123!',
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@futurelegacyschool.edu',
            'password' => 'CorrectHorse123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_logout_clears_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }
}
