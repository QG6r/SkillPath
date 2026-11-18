<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $courses = Course::query()
            ->with('teacher:id,name')
            ->withCount(['modules', 'students'])
            ->orderBy('title')
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'teacher' => $course->teacher?->name,
                'is_published' => $course->is_published,
                'modules_count' => $course->modules_count,
                'students_count' => $course->students_count,
            ]);

        return Inertia::render('Admin/Courses/Index', ['courses' => $courses]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Courses/Edit', [
            'course' => null,
            'modules' => [],
            'teachers' => $this->teachers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);

        $course = Course::create($data);

        return redirect()
            ->route('admin.courses.edit', $course)
            ->with('success', 'Course created. Add its modules below.');
    }

    public function edit(Course $course): Response
    {
        $modules = $course->modules()->withCount('topics')->with('task:id,module_id')->get()->map(fn ($module) => [
            'id' => $module->id,
            'title' => $module->title,
            'order' => $module->order,
            'topics_count' => $module->topics_count,
            'has_task' => $module->task !== null,
        ]);

        return Inertia::render('Admin/Courses/Edit', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'teacher_id' => $course->teacher_id,
                'is_published' => $course->is_published,
            ],
            'modules' => $modules,
            'teachers' => $this->teachers(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validated($request));

        return back()->with('success', 'Course saved.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'teacher_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', User::ROLE_TEACHER),
            ],
            'is_published' => ['boolean'],
        ]);
    }

    private function teachers()
    {
        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'course';
        $slug = $base;
        $suffix = 2;

        while (Course::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
