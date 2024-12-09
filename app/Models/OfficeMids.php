<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeMids extends Model
{
    use HasFactory;
    protected $table = 'office_m_ids';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    protected $casts = [
        'status' => 'boolean',
    ];

    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
