<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_three_accounts_and_a_course_ready_to_use(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame('admin', User::where('email', 'admin@skillpath.test')->value('role'));
        $this->assertSame('teacher', User::where('email', 'teacher@skillpath.test')->value('role'));
        $this->assertSame('student', User::where('email', 'student@skillpath.test')->value('role'));
        $this->assertTrue(Hash::check('password', User::where('email', 'student@skillpath.test')->value('password')));

        $this->assertDatabaseCount('courses', 1);
        $this->assertDatabaseCount('modules', 3);
        $this->assertDatabaseCount('topics', 6);
        $this->assertDatabaseCount('tasks', 3);
        $this->assertDatabaseHas('courses', [
            'slug' => 'java-basics',
            'is_published' => true,
            'teacher_id' => User::where('email', 'teacher@skillpath.test')->value('id'),
        ]);
    }

    public function test_running_the_seeder_twice_does_not_duplicate_anything(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('courses', 1);
        $this->assertDatabaseCount('modules', 3);
    }

    public function test_a_seeded_student_can_log_in_and_sees_the_course_in_the_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/login', ['email' => 'student@skillpath.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('student.courses.index'))->assertOk()->assertSee('Java Basics', false);
    }
}
