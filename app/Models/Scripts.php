<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scripts extends Model
{
    protected $table = 'scripts';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    use HasFactory;

    public static function getValidationRules($id = "")
    {
        return [
            'title' => 'required',
            'source' => [!$id ? 'required' : '', 'mimes:pdf'],
        ];
    }
    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
