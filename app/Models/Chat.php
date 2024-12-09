<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_1',
        'user_2',
    ];
    public function user_1(){
        return $this->belongsTo(User::class,'user_1');
    }
    public function user_2(){
        return $this->belongsTo(User::class,'user_2');
    }
    public function messages(){
        return $this->hasMany(Message::class,'chat_id')->orderBy('created_at');
    }
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('M d, Y \a\t h:i A');
    }
}
