<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The final work of a module. A student passes the module when a teacher accepts a submission for it.
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = ['module_id', 'title', 'instructions'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
