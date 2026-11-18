<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Submission;
use App\Services\CourseProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /** The student hands in the final work of a module: a GitHub link and a message for the teacher. */
    public function store(Request $request, Module $module, CourseProgress $progress): RedirectResponse
    {
        $task = $module->task;
        abort_if($task === null, 404);

        $course = $module->course;
        $student = $request->user();

        abort_unless($progress->isEnrolled($student, $course), 403, 'Join the course first.');

        $state = $progress->moduleStates($student, $course)[$module->id];

        abort_unless($progress->canOpen($state), 403, 'Finish the previous module first.');

        if ($state === CourseProgress::PENDING) {
            return back()->withErrors(['github_url' => 'Your previous work is still waiting for the teacher.']);
        }

        if ($state === CourseProgress::PASSED) {
            return back()->withErrors(['github_url' => 'This module is already passed.']);
        }

        $data = $request->validate([
            'github_url' => ['required', 'string', 'max:255', 'regex:#^https://(www\.)?github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+#'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'github_url.regex' => 'Enter a link to a GitHub repository, for example https://github.com/your-name/your-project',
        ]);

        Submission::create([
            'task_id' => $task->id,
            'user_id' => $student->id,
            'github_url' => $data['github_url'],
            'message' => $data['message'] ?? null,
            'status' => Submission::STATUS_PENDING,
        ]);

        return back()->with('success', 'Your work was sent to the teacher.');
    }
}
