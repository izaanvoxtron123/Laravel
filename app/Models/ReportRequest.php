<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportRequest extends Model
{
    use HasFactory;
    protected $table = 'report_requests';
    protected $guarded = ['id'];
    protected $appends = ['e_id','e_report_id'];
    protected $casts = [
        'status' => 'boolean',
    ];


    public static function getValidationRules($id = "")
    {
        return [
            'customer_id' => !$id ?'required' : '',
            'priority' => 'required',
            'type' => 'required',
        ];
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function agent()
    {
        return $this->hasOne(User::class, 'id', 'agent_id');
    }
    public function manager()
    {
        return $this->hasOne(User::class, 'id', 'manager_id');
    }

    public function getEReportIdAttribute(){
        return encrypt($this->report_id);
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
