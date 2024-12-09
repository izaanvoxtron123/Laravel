<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $table = 'notifications';
    protected $guarded = ['id'];

    
    // public function getPayloadAttribute($value){
    //     return json_decode($value);
    // }
}
