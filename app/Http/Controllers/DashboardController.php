<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Sends every signed-in user to the home page of their role. */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route(match ($request->user()->role) {
            User::ROLE_ADMIN => 'admin.courses.index',
            User::ROLE_TEACHER => 'teacher.submissions.index',
            default => 'student.dashboard',
        });
    }
}
