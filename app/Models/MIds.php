<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MIds extends Model
{
    protected $table = 'm_ids';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    use HasFactory, SoftDeletes;

    public static function getValidationRules($id = "")
    {
        return [
            'name' => 'required',
        ];
    }
    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
