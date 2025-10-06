<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'category_id',
        'instructor_id', 'thumbnail', 'level', 'language', 'duration'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('progress', 'completed_at')
            ->withTimestamps();
    }
}
