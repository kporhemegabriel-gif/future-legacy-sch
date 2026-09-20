<?php

namespace Tests\Feature\Admin;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_a_parent_linked_to_multiple_children(): void
    {
        $child1 = Student::factory()->create();
        $child2 = Student::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/parents', [
            'name' => 'Kwabena Sarpong',
            'email' => 'kwabena.sarpong@example.com',
            'password' => 'password123',
            'first_name' => 'Kwabena',
            'last_name' => 'Sarpong',
            'links' => [
                ['student_id' => $child1->id, 'relationship' => 'Father', 'is_primary' => '1'],
                ['student_id' => $child2->id, 'relationship' => 'Father', 'is_primary' => '1'],
            ],
        ]);

        $response->assertRedirect();

        $parent = ParentGuardian::whereHas('user', fn ($q) => $q->where('email', 'kwabena.sarpong@example.com'))->firstOrFail();
        $this->assertCount(2, $parent->students);
    }

    public function test_only_one_guardian_can_be_primary_per_student(): void
    {
        $child = Student::factory()->create();
        $parent1 = ParentGuardian::factory()->create();
        $parent2 = ParentGuardian::factory()->create();

        $parent1->syncStudent($child, 'Father', true);
        $parent2->syncStudent($child, 'Mother', true);

        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent1->id, 'student_id' => $child->id, 'is_primary' => false]);
        $this->assertDatabaseHas('parent_student', ['parent_id' => $parent2->id, 'student_id' => $child->id, 'is_primary' => true]);
    }

    public function test_parent_can_view_only_their_own_linked_children(): void
    {
        $ownChild = Student::factory()->create();
        $otherChild = Student::factory()->create();

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($ownChild, 'Mother', true);

        $this->actingAs($parentUser)->get("/parent/children/{$ownChild->id}")->assertOk();
        $this->actingAs($parentUser)->get("/parent/children/{$otherChild->id}")->assertForbidden();
    }

    public function test_non_admin_cannot_manage_parents(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser)->get('/admin/parents')->assertForbidden();
    }
}
