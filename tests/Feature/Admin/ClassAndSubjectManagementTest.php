<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassAndSubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_a_class(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/classes', [
            'name' => 'Grade 10',
            'section' => 'Green',
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_classes', ['name' => 'Grade 10', 'section' => 'Green']);
    }

    public function test_class_cannot_be_deleted_while_students_are_assigned(): void
    {
        $class = SchoolClass::factory()->create();
        Student::factory()->create(['class_id' => $class->id]);

        $this->actingAs($this->admin())->delete("/admin/classes/{$class->id}");

        $this->assertDatabaseHas('school_classes', ['id' => $class->id]);
    }

    public function test_admin_can_create_a_subject(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/subjects', [
            'code' => 'HIST101',
            'name' => 'History',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['code' => 'HIST101']);
    }

    public function test_duplicate_subject_code_is_rejected(): void
    {
        Subject::factory()->create(['code' => 'MATH101']);

        $response = $this->actingAs($this->admin())->post('/admin/subjects', [
            'code' => 'MATH101',
            'name' => 'Mathematics (duplicate)',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_assign_subjects_to_a_class(): void
    {
        $class = SchoolClass::factory()->create();
        $subject = Subject::factory()->create();

        $response = $this->actingAs($this->admin())->put("/admin/classes/{$class->id}/subjects", [
            'subject_ids' => [$subject->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subjects', [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $class->academic_year_id,
        ]);
    }

    public function test_non_admin_cannot_manage_classes_or_subjects(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parentUser)->get('/admin/classes')->assertForbidden();
        $this->actingAs($parentUser)->get('/admin/subjects')->assertForbidden();
    }
}
