<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeIps extends Model
{
    use HasFactory;
    protected $table = 'office_ips';
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
