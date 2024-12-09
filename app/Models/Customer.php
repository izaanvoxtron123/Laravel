<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'customers';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    protected $casts = [
        'status' => 'boolean',
        'is_complete' => 'integer',
        'in_rework' => 'integer',
    ];


    public static function getValidationRules($id = "")
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required|min:8|max:10',
            'email' => 'nullable|email',
            'ssn' => 'required|min:9',
            // 'dob' => 'required',
            // 'house_number' => 'required',
            // 'quadrant' => 'required',
            'street_name' => 'required',
            // 'street_type' => 'required',
            'city' => 'required',
            'state' => 'required',
            // 'meta' => 'max:300',
            'zip' => 'required',
            // 'source' => [!$id ? 'required' : '', 'mimes:png,jpg,PNG,JPG,image/gif,gif'],
        ];
    }

    public static function getProgress()
    {
        return [
            'PENDING',
            'NQ',
            'NA',
            'NI',
            'CALL BACK',
            'TRANSFER',
            'BUSY',
            'NUMBER NOT IN SERVICE',
            'VM',
            'D Transfer'
        ];
    }

    public function MId()
    {
        return $this->belongsTo(MIds::class, 'm_id');
    }


    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function office()
    {
        return $this->belongsTo(Offices::class, 'office_id');
    }

    public function to_person()
    {
        return $this->belongsTo(User::class, 'to_person_id');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closer_id');
    }



    public function rna_specialist()
    {
        return $this->belongsTo(User::class, 'specialist_rna_id');
    }
    public function cb_specialist()
    {
        return $this->belongsTo(User::class, 'specialist_cb_id');
    }
    public function decline_specialist()
    {
        return $this->belongsTo(User::class, 'specialist_decline_id');
    }


    public function reports()
    {
        return $this->hasMany(Report::class, 'customer_id')->orderBy('id', 'desc');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function accounts()
    {
        return $this->hasMany(CustomerAccounts::class, 'customer_id');
    }
    public function charge_accounts()
    {
        return $this->hasMany(CustomerAccounts::class, 'customer_id')
            ->where('charge_card', true);
    }

    public function recordings()
    {
        return $this->hasMany(CustomerRecordings::class, 'customer_id');
    }

    public function phones()
    {
        return $this->hasMany(CustomerPhones::class, 'customer_id');
    }

    public function logs()
    {
        return $this->hasMany(CustomerLogs::class, 'customer_id');
    }

    public function report_requests()
    {
        return $this->hasMany(ReportRequest::class);
    }

    public function attached_report()
    {
        $report_requests = $this->report_requests();
        $requests_with_report = $report_requests->where('report_id', '!=', null);
        return $requests_with_report->first();
    }

    // public function getSecondaryPhonesAttribute($value)
    // {
    //     if ($value != null) {
    //         $decodedValue = json_decode($value, true);
    //         if (is_array($decodedValue)) {
    //             return $decodedValue;
    //         }
    //     }
    // }

    public function getSourceAttribute($value)
    {
        return asset('storage/' . $value);
    }
    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
