<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\User;
use App\Services\CourseProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    /** Queue of final works of the courses this teacher is assigned to. */
    public function index(Request $request): Response
    {
        $teacher = $request->user();
        $status = $request->query('status', Submission::STATUS_PENDING);

        $query = Submission::query()
            ->whereHas('task.module.course', fn ($course) => $course->where('teacher_id', $teacher->id))
            ->with('student:id,name', 'task.module.course:id,title');

        if (in_array($status, [Submission::STATUS_PENDING, Submission::STATUS_CORRECT, Submission::STATUS_INCORRECT], true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        // The ones waiting longest come first; reviewed ones show the newest first.
        $query = $status === Submission::STATUS_PENDING ? $query->orderBy('id') : $query->orderByDesc('id');

        $submissions = $query->paginate(15)->withQueryString()->through(fn ($submission) => [
            'id' => $submission->id,
            'student' => $submission->student->name,
            'course' => $submission->task->module->course->title,
            'module' => $submission->task->module->title,
            'github_url' => $submission->github_url,
            'status' => $submission->status,
            'created_at' => $submission->created_at->toDateTimeString(),
        ]);

        $pendingCount = Submission::query()
            ->where('status', Submission::STATUS_PENDING)
            ->whereHas('task.module.course', fn ($course) => $course->where('teacher_id', $teacher->id))
            ->count();

        return Inertia::render('Teacher/Submissions/Index', [
            'submissions' => $submissions,
            'status' => $status,
            'pending_count' => $pendingCount,
        ]);
    }

    public function show(Request $request, Submission $submission): Response
    {
        $this->authorizeTeacher($request->user(), $submission);

        $submission->load('student:id,name,email', 'task.module.course:id,title');

        // Earlier attempts of the same student at the same final work.
        $history = Submission::query()
            ->where('task_id', $submission->task_id)
            ->where('user_id', $submission->user_id)
            ->where('id', '<', $submission->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($previous) => [
                'id' => $previous->id,
                'github_url' => $previous->github_url,
                'status' => $previous->status,
                'feedback' => $previous->feedback,
                'created_at' => $previous->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Teacher/Submissions/Show', [
            'submission' => [
                'id' => $submission->id,
                'github_url' => $submission->github_url,
                'message' => $submission->message,
                'status' => $submission->status,
                'feedback' => $submission->feedback,
                'created_at' => $submission->created_at->toDateTimeString(),
                'student' => [
                    'name' => $submission->student->name,
                    'email' => $submission->student->email,
                ],
                'course' => $submission->task->module->course->title,
                'module' => $submission->task->module->title,
                'task' => [
                    'title' => $submission->task->title,
                    'instructions' => $submission->task->instructions,
                ],
            ],
            'history' => $history,
        ]);
    }

    /** Marks the work correct (the student's next module opens) or incorrect (the student can hand in again). */
    public function review(Request $request, Submission $submission, CourseProgress $progress): RedirectResponse
    {
        $teacher = $request->user();
        $this->authorizeTeacher($teacher, $submission);

        if (! $submission->isPending()) {
            return back()->with('error', 'This work has already been reviewed.');
        }

        $data = $request->validate([
            'status' => ['required', 'in:'.Submission::STATUS_CORRECT.','.Submission::STATUS_INCORRECT],
            'feedback' => ['required_if:status,'.Submission::STATUS_INCORRECT, 'nullable', 'string', 'max:5000'],
        ], [
            'feedback.required_if' => 'Write what the student should fix.',
        ]);

        $submission->update([
            'status' => $data['status'],
            'feedback' => $data['feedback'] ?? null,
            'reviewed_by' => $teacher->id,
            'reviewed_at' => now(),
        ]);

        if ($submission->status === Submission::STATUS_CORRECT) {
            $course = $submission->task->module->course;
            $progress->syncEnrollment($submission->student, $course);
        }

        return redirect()
            ->route('teacher.submissions.index')
            ->with('success', 'The review was saved.');
    }

    /** A teacher only works with the courses an admin assigned to them. */
    private function authorizeTeacher(User $teacher, Submission $submission): void
    {
        $submission->loadMissing('task.module.course');

        abort_unless($submission->task->module->course->teacher_id === $teacher->id, 403);
    }
}
