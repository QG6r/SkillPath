<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $module = $course->modules()->create([
            'title' => $data['title'],
            'order' => ((int) $course->modules()->max('order')) + 1,
        ]);

        return redirect()
            ->route('admin.modules.edit', $module)
            ->with('success', 'Module created. Add its topics and the final work.');
    }

    public function edit(Module $module): Response
    {
        $module->load('course:id,title', 'topics', 'task');

        return Inertia::render('Admin/Modules/Edit', [
            'course' => ['id' => $module->course->id, 'title' => $module->course->title],
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'order' => $module->order,
            ],
            'topics' => $module->topics->map(fn ($topic) => [
                'id' => $topic->id,
                'title' => $topic->title,
                'content' => $topic->content,
                'video_url' => $topic->video_url,
                'order' => $topic->order,
            ]),
            'task' => $module->task ? [
                'title' => $module->task->title,
                'instructions' => $module->task->instructions,
            ] : null,
        ]);
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $module->update($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'order' => ['required', 'integer', 'min:1', 'max:1000'],
        ]));

        return back()->with('success', 'Module saved.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        $course = $module->course;
        $module->delete();

        return redirect()->route('admin.courses.edit', $course)->with('success', 'Module deleted.');
    }
}
