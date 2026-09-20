<?php

namespace Tests\Feature;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_parent_cannot_access_admin_dashboard(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_protected_routes(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_deactivated_user_is_logged_out_on_access_attempt(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'inactive']);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertForbidden();
        $this->assertGuest();
    }

    public function test_parent_dashboard_only_shows_their_own_linked_children(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);

        $ownChild = Student::factory()->create();
        $otherChild = Student::factory()->create();
        $parentProfile->students()->attach($ownChild->id, ['relationship' => 'Mother']);

        $response = $this->actingAs($parentUser)->get('/parent/dashboard');

        $response->assertOk();
        $response->assertSee($ownChild->admission_number);
        $response->assertDontSee($otherChild->admission_number);
    }
}
