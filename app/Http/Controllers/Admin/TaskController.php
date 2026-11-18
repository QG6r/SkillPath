<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The final work of a module: one per module, created or replaced from a single form. */
class TaskController extends Controller
{
    public function update(Request $request, Module $module): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:10000'],
        ]);

        $module->task()->updateOrCreate(['module_id' => $module->id], $data);

        return back()->with('success', 'Final work saved.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        $module->task?->delete();

        return back()->with('success', 'Final work removed.');
    }
}
