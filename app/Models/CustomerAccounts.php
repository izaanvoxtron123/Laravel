<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAccounts extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_accounts';
    protected $guarded = ['id'];
    protected $casts = [
        'status' => 'boolean',
        'charge_card' => 'boolean',
    ];
}
