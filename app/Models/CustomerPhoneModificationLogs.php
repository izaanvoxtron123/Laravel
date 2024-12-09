<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPhoneModificationLogs extends Model
{
    use HasFactory;
    protected $table = 'customer_phone_modification_logs';
    protected $guarded = ['id'];


    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
