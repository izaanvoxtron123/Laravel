<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personnel extends Model
{
    use HasFactory;
    protected $table = 'personnel';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    protected $casts = [
        'status' => 'boolean',
    ];
    
    public const T_O_PERSON = "T.O Person";
    public const T_L_NAME = "T.L Name";
    public const CLOSER_NAME = "Closer Name";


    public static function getValidationRules($id = "")
    {
        return [
            'type' => 'required',
            'name' => 'required',
        ];
    }


    
    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
