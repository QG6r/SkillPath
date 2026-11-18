<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function store(Request $request, Module $module): RedirectResponse
    {
        $data = $this->validated($request);
        $data['order'] = ((int) $module->topics()->max('order')) + 1;

        $module->topics()->create($data);

        return back()->with('success', 'Topic added.');
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        $data = $this->validated($request);
        $data['order'] = $request->validate(['order' => ['required', 'integer', 'min:1', 'max:1000']])['order'];

        $topic->update($data);

        return back()->with('success', 'Topic saved.');
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        $topic->delete();

        return back()->with('success', 'Topic deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:20000'],
            'video_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);
    }
}
