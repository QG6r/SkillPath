<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use BuildsCourses;
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach (['/dashboard', '/student/dashboard', '/student/courses', '/teacher/submissions', '/admin/courses', '/admin/users'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_the_dashboard_sends_each_role_to_its_own_home(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')->assertRedirect(route('student.dashboard'));

        $this->actingAs(User::factory()->teacher()->create())
            ->get('/dashboard')->assertRedirect(route('teacher.submissions.index'));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')->assertRedirect(route('admin.courses.index'));
    }

    public function test_a_student_cannot_use_teacher_or_admin_pages(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('teacher.submissions.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.courses.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.courses.store'), ['title' => 'X', 'description' => 'Y'])->assertForbidden();
    }

    public function test_a_teacher_cannot_use_student_or_admin_pages(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('student.courses.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.courses.index'))->assertForbidden();
    }

    public function test_an_admin_cannot_use_student_or_teacher_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('student.courses.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('teacher.submissions.index'))->assertForbidden();
    }

    public function test_every_role_can_open_its_home_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('student.dashboard'))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('student.courses.index'))->assertOk();
        $this->actingAs(User::factory()->teacher()->create())->get(route('teacher.submissions.index'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.courses.index'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.users.index'))->assertOk();
    }

    public function test_registration_always_creates_a_student(): void
    {
        $this->post('/register', [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertSame('student', User::where('email', 'new@example.com')->firstOrFail()->role);
    }

    public function test_the_catalog_lists_only_published_courses(): void
    {
        $published = $this->makeCourse(1);
        \App\Models\Course::factory()->draft()->create(['title' => 'Secret Draft']);

        $this->actingAs(User::factory()->create())
            ->get(route('student.courses.index'))
            ->assertOk()
            ->assertSee($published->title, false)
            ->assertDontSee('Secret Draft', false);
    }
}
