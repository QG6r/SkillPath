<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /** Catalog of published courses. */
    public function index(Request $request): Response
    {
        $enrolledIds = $request->user()->coursesEnrolled()->pluck('courses.id')->all();

        $courses = Course::query()
            ->where('is_published', true)
            ->with('teacher:id,name')
            ->withCount('modules')
            ->orderBy('title')
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'teacher' => $course->teacher?->name,
                'modules_count' => $course->modules_count,
                'enrolled' => in_array($course->id, $enrolledIds, true),
            ]);

        return Inertia::render('Student/Courses/Index', ['courses' => $courses]);
    }

    public function show(Request $request, Course $course, CourseProgress $progress): Response
    {
        abort_unless($course->is_published, 404);

        $student = $request->user();
        $enrolled = $progress->isEnrolled($student, $course);
        $states = $enrolled ? $progress->moduleStates($student, $course) : [];

        $modules = $course->modules()->withCount('topics')->get()->map(fn ($module) => [
            'id' => $module->id,
            'title' => $module->title,
            'description' => $module->description,
            'topics_count' => $module->topics_count,
            // Only a student who joined the course sees where they stand in each module.
            'state' => $states[$module->id] ?? null,
        ]);

        return Inertia::render('Student/Courses/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'teacher' => $course->teacher?->name,
            ],
            'enrolled' => $enrolled,
            'progress' => $enrolled ? $progress->percent($student, $course) : null,
            'modules' => $modules,
        ]);
    }

    /** Joining a course is free: the student just picks it. */
    public function enroll(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->is_published, 404);

        $request->user()->coursesEnrolled()->syncWithoutDetaching([$course->id]);

        return redirect()
            ->route('student.courses.show', $course)
            ->with('success', 'You joined the course. Module 1 is open.');
    }
}
