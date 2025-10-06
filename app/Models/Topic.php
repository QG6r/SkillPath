<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = ['module_id', 'title', 'content', 'video_url', 'order'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function task()
    {
        return $this->hasOne(Task::class);
    }
}
