<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Topic;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'order'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
