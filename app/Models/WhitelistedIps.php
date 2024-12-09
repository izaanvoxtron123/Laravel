<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhitelistedIps extends Model
{
    use HasFactory, SoftDeletes;

    
    protected $table = 'whitelisted_ips';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    protected $casts = [
        'status' => 'boolean',
    ];

    public static function getValidationRules($id = "")
    {
        return [
            'ip' => 'required',
        ];
    }

    function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
