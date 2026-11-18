<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_ADMIN = 'admin';

    public const ROLES = [self::ROLE_STUDENT, self::ROLE_TEACHER, self::ROLE_ADMIN];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Courses whose final works this user reviews (a teacher). */
    public function coursesTeaching()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    /** Courses a student has joined. */
    public function coursesEnrolled()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('progress', 'completed_at')
            ->withTimestamps();
    }

    /** Final works handed in by a student. */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
