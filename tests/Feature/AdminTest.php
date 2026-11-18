<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Module;
use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use BuildsCourses;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_an_admin_creates_a_course_and_assigns_a_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), [
            'title' => 'Laravel Basics',
            'description' => 'Routes, controllers and a small project.',
            'teacher_id' => $teacher->id,
            'is_published' => true,
        ]);

        $course = Course::where('title', 'Laravel Basics')->firstOrFail();
        $response->assertRedirect(route('admin.courses.edit', $course));
        $this->assertSame('laravel-basics', $course->slug);
        $this->assertSame($teacher->id, $course->teacher_id);
        $this->assertTrue($course->is_published);
    }

    public function test_two_courses_with_the_same_title_get_different_slugs(): void
    {
        foreach (range(1, 2) as $ignored) {
            $this->actingAs($this->admin)->post(route('admin.courses.store'), [
                'title' => 'Same Title',
                'description' => 'Text',
            ]);
        }

        $this->assertEqualsCanonicalizing(['same-title', 'same-title-2'], Course::pluck('slug')->all());
    }

    public function test_only_a_teacher_can_be_assigned_to_a_course(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.courses.store'), [
            'title' => 'Course',
            'description' => 'Text',
            'teacher_id' => $student->id,
        ])->assertSessionHasErrors('teacher_id');

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_a_course_can_be_hidden_and_changed_back_to_a_draft(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.courses.update', $course), [
            'title' => 'Renamed',
            'description' => 'New text',
            'is_published' => false,
        ])->assertRedirect();

        $course->refresh();
        $this->assertSame('Renamed', $course->title);
        $this->assertFalse($course->is_published);
    }

    public function test_modules_are_added_at_the_end(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.modules.store', $course), ['title' => 'First']);
        $this->actingAs($this->admin)->post(route('admin.modules.store', $course), ['title' => 'Second']);

        $this->assertSame(['First' => 1, 'Second' => 2], $course->modules()->pluck('order', 'title')->all());
    }

    public function test_topics_are_added_in_order_and_a_video_link_must_be_http(): void
    {
        $module = Module::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.topics.store', $module), [
            'title' => 'Intro',
            'content' => 'Welcome',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $this->actingAs($this->admin)->post(route('admin.topics.store', $module), ['title' => 'Second']);
        $this->actingAs($this->admin)->post(route('admin.topics.store', $module), [
            'title' => 'Bad',
            'video_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('video_url');

        $this->assertSame(['Intro' => 1, 'Second' => 2], $module->topics()->pluck('order', 'title')->all());
    }

    public function test_a_topic_can_be_edited_and_deleted(): void
    {
        $topic = Topic::factory()->create(['title' => 'Old']);

        $this->actingAs($this->admin)->put(route('admin.topics.update', $topic), [
            'title' => 'New',
            'content' => 'Body',
            'video_url' => '',
            'order' => 3,
        ]);

        $topic->refresh();
        $this->assertSame('New', $topic->title);
        $this->assertSame(3, $topic->order);
        $this->assertNull($topic->video_url);

        $this->actingAs($this->admin)->delete(route('admin.topics.destroy', $topic));
        $this->assertDatabaseMissing('topics', ['id' => $topic->id]);
    }

    public function test_a_module_has_one_final_work_that_is_replaced_on_save(): void
    {
        $module = Module::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $module), ['title' => 'One', 'instructions' => 'Do it']);
        $this->actingAs($this->admin)->put(route('admin.tasks.update', $module), ['title' => 'Two', 'instructions' => 'Do it better']);

        $this->assertSame(1, Task::where('module_id', $module->id)->count());
        $this->assertSame('Two', $module->task()->first()->title);

        $this->actingAs($this->admin)->delete(route('admin.tasks.destroy', $module));
        $this->assertSame(0, Task::where('module_id', $module->id)->count());
    }

    public function test_the_module_page_and_course_page_open_for_an_admin(): void
    {
        $course = $this->makeCourse(1);

        $this->actingAs($this->admin)->get(route('admin.courses.edit', $course))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.modules.edit', $course->modules[0]))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.courses.create'))->assertOk();
    }

    public function test_deleting_a_course_removes_its_modules_and_works(): void
    {
        $course = $this->makeCourse(2);

        $this->actingAs($this->admin)->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('modules', 0);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_an_admin_creates_a_teacher_account(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'New Teacher',
            'email' => 'teacher2@example.com',
            'password' => 'a-long-password',
            'role' => 'teacher',
        ]);

        $teacher = User::where('email', 'teacher2@example.com')->firstOrFail();
        $this->assertTrue($teacher->isTeacher());

        // The new teacher can sign in with the password the admin set.
        $this->post('/logout');
        $this->post('/login', ['email' => 'teacher2@example.com', 'password' => 'a-long-password']);
        $this->assertAuthenticatedAs($teacher);
    }

    public function test_an_admin_can_change_the_role_of_another_user_but_not_their_own(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin)->patch(route('admin.users.update', $student), ['role' => 'teacher']);
        $this->assertSame('teacher', $student->fresh()->role);

        $this->actingAs($this->admin)->patch(route('admin.users.update', $this->admin), ['role' => 'student']);
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_an_unknown_role_is_rejected(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.users.update', $student), ['role' => 'superuser'])
            ->assertSessionHasErrors('role');
    }
}
