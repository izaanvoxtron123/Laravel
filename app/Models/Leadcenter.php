<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leadcenter extends Model
{
    protected $table = 'leadcenter';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    use HasFactory, SoftDeletes;

    function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    function rnd_agent()
    {
        return $this->belongsTo(User::class, 'rnd_agent_id');
    }

    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
