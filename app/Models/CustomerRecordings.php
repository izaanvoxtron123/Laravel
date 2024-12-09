<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRecordings extends Model
{
    use HasFactory;
    protected $table = 'customer_recordings';
    protected $guarded = ['id'];
    protected $casts = [
        'status' => 'boolean',
    ];
}
