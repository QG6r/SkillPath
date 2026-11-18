<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Module;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * The main story of the app: join a course, hand in the final work of a module,
 * get it checked by the teacher, and see the next module open.
 */
class CourseFlowTest extends TestCase
{
    use BuildsCourses;
    use RefreshDatabase;

    public function test_joining_a_course_opens_only_the_first_module(): void
    {
        $course = $this->makeCourse(3);
        $student = User::factory()->create();

        $this->actingAs($student)
            ->post(route('student.courses.enroll', $course))
            ->assertRedirect(route('student.courses.show', $course));

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Courses/Show')
                ->where('enrolled', true)
                ->where('progress', 0)
                ->where('modules.0.state', 'open')
                ->where('modules.1.state', 'locked')
                ->where('modules.2.state', 'locked'));
    }

    public function test_before_joining_the_student_sees_the_modules_without_states(): void
    {
        $course = $this->makeCourse(2);

        $this->actingAs(User::factory()->create())
            ->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('enrolled', false)
                ->where('modules.0.state', null));
    }

    public function test_a_locked_module_cannot_be_opened_or_submitted_to(): void
    {
        $course = $this->makeCourse(2);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $second = $course->modules[1];

        $this->actingAs($student)
            ->get(route('student.modules.show', [$course, $second]))
            ->assertForbidden();

        $this->submitWork($student, $course, 2);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_a_student_who_has_not_joined_cannot_open_a_module(): void
    {
        $course = $this->makeCourse(1);

        $this->actingAs(User::factory()->create())
            ->get(route('student.modules.show', [$course, $course->modules[0]]))
            ->assertForbidden();
    }

    public function test_an_unpublished_course_is_not_available_to_students(): void
    {
        $course = Course::factory()->draft()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('student.courses.show', $course))
            ->assertNotFound();
    }

    public function test_the_student_hands_in_a_github_link_with_a_message(): void
    {
        $course = $this->makeCourse(2);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);

        $this->submitWork($student, $course, 1, 'https://github.com/student/hello-world');

        $this->assertDatabaseHas('submissions', [
            'user_id' => $student->id,
            'task_id' => $course->modules[0]->task->id,
            'github_url' => 'https://github.com/student/hello-world',
            'message' => 'Please check my work.',
            'status' => 'pending',
        ]);
    }

    public function test_only_a_github_repository_link_is_accepted(): void
    {
        $course = $this->makeCourse(1);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);

        foreach (['not a link', 'https://example.com/student/project', 'https://github.com/student', 'javascript:alert(1)'] as $url) {
            $this->actingAs($student)
                ->post(route('student.submissions.store', $course->modules[0]), ['github_url' => $url])
                ->assertSessionHasErrors('github_url');
        }

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_a_second_work_cannot_be_sent_while_the_first_is_waiting(): void
    {
        $course = $this->makeCourse(1);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);

        $this->submitWork($student, $course);
        $this->submitWork($student, $course, 1, 'https://github.com/student/other');

        $this->assertDatabaseCount('submissions', 1);
        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page->where('modules.0.state', 'pending'));
    }

    public function test_an_accepted_work_opens_the_next_module_and_updates_progress(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(2, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);

        $this->actingAs($teacher)
            ->patch(route('teacher.submissions.review', $this->latestSubmission()), ['status' => 'correct'])
            ->assertRedirect(route('teacher.submissions.index'));

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->where('progress', 50)
                ->where('modules.0.state', 'passed')
                ->where('modules.1.state', 'open'));

        $this->actingAs($student)
            ->get(route('student.modules.show', [$course, $course->modules[1]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Modules/Show')->where('state', 'open'));

        $this->assertSame(50, (int) $student->coursesEnrolled()->first()->pivot->progress);
    }

    public function test_finishing_every_module_completes_the_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(1, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);

        $this->actingAs($teacher)
            ->patch(route('teacher.submissions.review', $this->latestSubmission()), ['status' => 'correct']);

        $enrollment = $student->coursesEnrolled()->first()->pivot;
        $this->assertSame(100, (int) $enrollment->progress);
        $this->assertNotNull($enrollment->completed_at);
    }

    public function test_a_rejected_work_keeps_the_next_module_locked_and_can_be_sent_again(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(2, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);

        $this->actingAs($teacher)
            ->patch(route('teacher.submissions.review', $this->latestSubmission()), [
                'status' => 'incorrect',
                'feedback' => 'The README is missing.',
            ]);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->where('modules.0.state', 'needs_fix')
                ->where('modules.1.state', 'locked'));

        $this->actingAs($student)
            ->get(route('student.modules.show', [$course, $course->modules[0]]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('can_submit', true)
                ->where('submissions.0.feedback', 'The README is missing.'));

        $this->submitWork($student, $course, 1, 'https://github.com/student/fixed');

        $this->assertDatabaseCount('submissions', 2);
        $this->assertSame('pending', $this->latestSubmission()->status);
    }

    public function test_the_latest_attempt_decides(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(2, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);

        $this->submitWork($student, $course);
        $this->actingAs($teacher)->patch(route('teacher.submissions.review', $this->latestSubmission()), [
            'status' => 'incorrect',
            'feedback' => 'Try again.',
        ]);
        $this->submitWork($student, $course, 1, 'https://github.com/student/second-try');
        $this->actingAs($teacher)->patch(route('teacher.submissions.review', $this->latestSubmission()), ['status' => 'correct']);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page->where('modules.0.state', 'passed')->where('modules.1.state', 'open'));
    }

    public function test_a_passed_module_does_not_accept_new_work(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(1, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);
        $this->actingAs($teacher)->patch(route('teacher.submissions.review', $this->latestSubmission()), ['status' => 'correct']);

        $this->submitWork($student, $course, 1, 'https://github.com/student/again');

        $this->assertDatabaseCount('submissions', 1);
    }

    public function test_a_module_without_a_final_work_does_not_block_the_next_one(): void
    {
        $course = $this->makeCourse(2);
        Task::query()->where('module_id', $course->modules[0]->id)->delete();
        $student = User::factory()->create();
        $this->joinCourse($student, $course);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->where('modules.0.state', 'passed')
                ->where('modules.1.state', 'open'));
    }

    public function test_modules_are_opened_in_the_order_set_by_the_admin(): void
    {
        $course = $this->makeCourse(2);
        // The admin moves the second module to the front.
        Module::query()->whereKey($course->modules[1]->id)->update(['order' => 0]);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->where('modules.0.id', $course->modules[1]->id)
                ->where('modules.0.state', 'open')
                ->where('modules.1.state', 'locked'));
    }

    public function test_a_teacher_only_reviews_works_of_their_own_courses(): void
    {
        $course = $this->makeCourse(1);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);
        $submission = $this->latestSubmission();
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($otherTeacher)->get(route('teacher.submissions.show', $submission))->assertForbidden();
        $this->actingAs($otherTeacher)
            ->patch(route('teacher.submissions.review', $submission), ['status' => 'correct'])
            ->assertForbidden();

        $this->assertSame('pending', $submission->fresh()->status);
    }

    public function test_the_queue_lists_only_the_works_of_the_teachers_courses(): void
    {
        $teacher = User::factory()->teacher()->create();
        $mine = $this->makeCourse(1, $teacher);
        $theirs = $this->makeCourse(1);
        $student = User::factory()->create();

        $this->joinCourse($student, $mine);
        $this->joinCourse($student, $theirs);
        $this->submitWork($student, $mine);
        $this->submitWork($student, $theirs);

        $this->actingAs($teacher)
            ->get(route('teacher.submissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teacher/Submissions/Index')
                ->where('pending_count', 1)
                ->has('submissions.data', 1)
                ->where('submissions.data.0.course', $mine->title));
    }

    public function test_an_incorrect_decision_needs_a_comment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(1, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);

        $this->actingAs($teacher)
            ->patch(route('teacher.submissions.review', $this->latestSubmission()), ['status' => 'incorrect'])
            ->assertSessionHasErrors('feedback');

        $this->assertSame('pending', $this->latestSubmission()->status);
    }

    public function test_a_reviewed_work_cannot_be_reviewed_again(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(1, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);
        $submission = $this->latestSubmission();

        $this->actingAs($teacher)->patch(route('teacher.submissions.review', $submission), ['status' => 'correct']);
        $this->actingAs($teacher)->patch(route('teacher.submissions.review', $submission), [
            'status' => 'incorrect',
            'feedback' => 'Changed my mind.',
        ]);

        $this->assertSame(Submission::STATUS_CORRECT, $submission->fresh()->status);
    }

    public function test_the_review_is_recorded_with_the_reviewer(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->makeCourse(1, $teacher);
        $student = User::factory()->create();
        $this->joinCourse($student, $course);
        $this->submitWork($student, $course);

        $this->actingAs($teacher)->patch(route('teacher.submissions.review', $this->latestSubmission()), [
            'status' => 'correct',
            'feedback' => 'Nice work.',
        ]);

        $submission = $this->latestSubmission();
        $this->assertSame($teacher->id, $submission->reviewed_by);
        $this->assertNotNull($submission->reviewed_at);
        $this->assertSame('Nice work.', $submission->feedback);
    }
}
