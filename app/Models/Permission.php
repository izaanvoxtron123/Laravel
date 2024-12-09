<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function child(){
        return $this->hasMany(self::class,'parent_id');
    }
}
