<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/home', function () {
        return Inertia::render('Student/Home', ['role' => 'student']);
    })->name('student.home');
});

Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/teacher/home', function () {
        return Inertia::render('Teacher/Home', ['role' => 'teacher']);
    })->name('teacher.home');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/home', function () {
        return Inertia::render('Admin/Home', ['role' => 'admin']);
    })->name('admin.home');
});

Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

