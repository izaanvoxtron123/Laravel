<?php

namespace App\Models;

use App\Http\Middleware\WhitelistIps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offices extends Model
{
    use HasFactory;
    protected $table = 'offices';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];
    protected $casts = [
        'status' => 'boolean',
    ];


    public static function getValidationRules($id = "")
    {
        return [
            'name' => 'required',
            'email_domain' => 'required',
            'ips' => 'required|array',
        ];
    }

    public function agents()
    {
        return $this->hasMany(User::class, 'office_id')->where('role_id', User::AGENT_ROLE);
    }


    public function rna_specialists()
    {
        return $this->hasMany(User::class, 'office_id')->where('role_id', User::RNA_SPECIALIST_ROLE);
    }

    public function decline_specialists()
    {
        return $this->hasMany(User::class, 'office_id')->where('role_id', User::DECLINE_SPECIALIST_ROLE);
    }

    public function cb_specialists()
    {
        return $this->hasMany(User::class, 'office_id')->where('role_id', User::CB_SPECIALIST_ROLE);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'office_id');
    }

    public function ips()
    {
        return $this->hasManyThrough(
            WhitelistedIps::class,
            OfficeIps::class,
            'office_id', // Foreign key on OfficeIps table
            'id', // Local key on Ips table
            'id', // Local key on Office table
            'ip_id' // Foreign key on OfficeIps table
        );
    }



    public function mids()
    {
        return $this->hasManyThrough(
            MIds::class,
            OfficeMids::class,
            'office_id',
            'id',
            'id',
            'mid_id'
        );
    }

    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
