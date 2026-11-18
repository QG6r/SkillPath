<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Topic;
use App\Services\CourseProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    public function show(Request $request, Course $course, Module $module, CourseProgress $progress): Response
    {
        abort_unless($module->course_id === $course->id && $course->is_published, 404);

        $student = $request->user();

        abort_unless($progress->isEnrolled($student, $course), 403, 'Join the course first.');

        $states = $progress->moduleStates($student, $course);
        $state = $states[$module->id];

        abort_unless($progress->canOpen($state), 403, 'Finish the previous module first.');

        $module->load('topics', 'task');
        $task = $module->task;

        $submissions = $task
            ? $task->submissions()
                ->where('user_id', $student->id)
                ->orderByDesc('id')
                ->get()
                ->map(fn ($submission) => [
                    'id' => $submission->id,
                    'github_url' => $submission->github_url,
                    'message' => $submission->message,
                    'status' => $submission->status,
                    'feedback' => $submission->feedback,
                    'created_at' => $submission->created_at->toDateTimeString(),
                ])
            : collect();

        // The next module, shown as a link once this one is passed.
        $next = null;
        if ($state === CourseProgress::PASSED) {
            $next = $course->modules()->where(function ($query) use ($module) {
                $query->where('order', '>', $module->order)
                    ->orWhere(fn ($same) => $same->where('order', $module->order)->where('id', '>', $module->id));
            })->first();
        }

        return Inertia::render('Student/Modules/Show', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
            ],
            'state' => $state,
            'topics' => $module->topics->map(fn ($topic) => [
                'id' => $topic->id,
                'title' => $topic->title,
                'content' => $topic->content,
                'video_url' => $topic->video_url,
                'embed_url' => Topic::toEmbedUrl($topic->video_url),
            ]),
            'task' => $task ? [
                'id' => $task->id,
                'title' => $task->title,
                'instructions' => $task->instructions,
            ] : null,
            'can_submit' => $task !== null && $progress->canSubmit($state),
            'submissions' => $submissions,
            'next_module_id' => $next?->id,
        ]);
    }
}
