<?php

namespace Tests\Concerns;

use App\Models\Course;
use App\Models\Module;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;

trait BuildsCourses
{
    /** A published course with $modules modules (order 1, 2, ...), each with a final work. */
    protected function makeCourse(int $modules = 2, ?User $teacher = null): Course
    {
        $teacher ??= User::factory()->teacher()->create();
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        foreach (range(1, $modules) as $order) {
            $module = Module::factory()->create(['course_id' => $course->id, 'order' => $order]);
            Task::factory()->create(['module_id' => $module->id]);
        }

        return $course->fresh(['modules.task']);
    }

    protected function joinCourse(User $student, Course $course): void
    {
        $this->actingAs($student)->post(route('student.courses.enroll', $course));
    }

    /** The student hands in the final work of the given module of the course (1 = first module). */
    protected function submitWork(User $student, Course $course, int $moduleNumber = 1, string $url = 'https://github.com/student/project'): void
    {
        $module = $course->modules[$moduleNumber - 1];

        $this->actingAs($student)->post(route('student.submissions.store', $module), [
            'github_url' => $url,
            'message' => 'Please check my work.',
        ]);
    }

    protected function latestSubmission(): Submission
    {
        return Submission::query()->orderByDesc('id')->firstOrFail();
    }
}
