<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPhones extends Model
{
    use HasFactory;

    protected $table = 'customer_phones';
    protected $guarded = ['id'];
    protected $casts = [
        'status' => 'boolean',
    ];
}
