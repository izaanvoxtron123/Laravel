<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneValidationLogs extends Model
{
    use HasFactory;
    protected $table = 'phone_validation_logs';
    protected $guarded = ['id'];
}
