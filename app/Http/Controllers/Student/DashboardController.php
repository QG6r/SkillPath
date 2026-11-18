<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\CourseProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, CourseProgress $progress): Response
    {
        $student = $request->user();

        $courses = $student->coursesEnrolled()
            ->with('teacher:id,name')
            ->withCount('modules')
            ->orderBy('enrollments.created_at', 'desc')
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'teacher' => $course->teacher?->name,
                'modules_count' => $course->modules_count,
                'progress' => $progress->percent($student, $course),
            ]);

        return Inertia::render('Student/Dashboard', ['courses' => $courses]);
    }
}
