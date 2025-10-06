<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Task;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'user_id', 'content', 'score', 'feedback'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
