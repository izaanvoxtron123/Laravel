<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    protected $casts = [
        'status' => 'boolean',
        'report' => 'array'
    ];

    public static function getValidationRules($id = "")
    {
        return [
            'report_type' => 'required',
            'firstName' => 'required',
            'lastName' => 'required',
            // 'phone' => 'required',
            // 'email' => 'required|email',
            'ssn' => 'required',
            // 'dob' => 'required',
            // 'houseNumber' => 'required',
            // 'quadrant' => 'required',
            'streetName' => 'required',
            // 'streetType' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
        ];
    }

    public function reportRequest()
    {
        return $this->belongsTo(ReportRequest::class, 'id', 'report_id');
    }

    function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
