<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    // Sends the user to the home page of their role.
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Student: picks a course, reads the modules, hands in the final work of each module.
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');

    Route::get('courses', [Student\CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [Student\CourseController::class, 'show'])->name('courses.show');
    Route::post('courses/{course}/enroll', [Student\CourseController::class, 'enroll'])->name('courses.enroll');

    Route::get('courses/{course}/modules/{module}', [Student\ModuleController::class, 'show'])->name('modules.show');
    Route::post('modules/{module}/submissions', [Student\SubmissionController::class, 'store'])->name('submissions.store');
});

// Teacher: reviews the final works of the courses assigned to them.
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('submissions', [Teacher\SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('submissions/{submission}', [Teacher\SubmissionController::class, 'show'])->name('submissions.show');
    Route::patch('submissions/{submission}', [Teacher\SubmissionController::class, 'review'])->name('submissions.review');
});

// Admin: builds courses and manages users.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('courses', Admin\CourseController::class)->except(['show']);

    Route::post('courses/{course}/modules', [Admin\ModuleController::class, 'store'])->name('modules.store');
    Route::get('modules/{module}/edit', [Admin\ModuleController::class, 'edit'])->name('modules.edit');
    Route::put('modules/{module}', [Admin\ModuleController::class, 'update'])->name('modules.update');
    Route::delete('modules/{module}', [Admin\ModuleController::class, 'destroy'])->name('modules.destroy');

    Route::post('modules/{module}/topics', [Admin\TopicController::class, 'store'])->name('topics.store');
    Route::put('topics/{topic}', [Admin\TopicController::class, 'update'])->name('topics.update');
    Route::delete('topics/{topic}', [Admin\TopicController::class, 'destroy'])->name('topics.destroy');

    Route::put('modules/{module}/task', [Admin\TaskController::class, 'update'])->name('tasks.update');
    Route::delete('modules/{module}/task', [Admin\TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
});
