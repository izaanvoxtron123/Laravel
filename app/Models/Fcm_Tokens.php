<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fcm_Tokens extends Model
{
    use HasFactory;
    protected $table = 'fcm_tokens';
    protected $guarded = ['id'];
}
