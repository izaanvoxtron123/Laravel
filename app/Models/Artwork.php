<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    use HasFactory;
    protected $table = 'artworks';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    protected $casts = [
        'status' => 'boolean',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'e_id',
    ];


    public static function getValidationRules($id = "")
    {
        return [
            'name' => 'required',
            'amount' => 'required',
            'description' => 'required',
            'source' => [!$id? 'required':'', 'mimes:png,jpg,PNG,JPG,image/gif,gif'],
        ];
    }

    public function getSourceAttribute($value)
    {
        return asset('storage/' . $value);
    }
    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
