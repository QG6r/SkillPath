<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Методы для проверки роли
    public function isStudent() { return $this->role === 'student'; }
    public function isTeacher() { return $this->role === 'teacher'; }
    public function isAdmin() { return $this->role === 'admin'; }

    public function coursesTeaching()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    // Курсы, на которые записан студент
    public function coursesEnrolled()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('progress', 'completed_at')
            ->withTimestamps();
    }

    // Задания студента
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
