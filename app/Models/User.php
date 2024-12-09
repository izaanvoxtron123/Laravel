<?php

namespace App\Models;

use App\Http\Common\Constant;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Validation\Rule;
use Auth;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    protected $with = ['role'];
    protected $guarded = ['id'];
    protected $appends = ['e_id', 'image_url'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
        'social_profile_type',
        'social_profile_id',
        'email_verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_subscribed' => 'boolean',
        'status' => 'boolean',
    ];

    public const SUPERADMIN_ROLE = 1;
    public const MANAGER_ROLE = 2;
    public const AGENT_ROLE = 3;
    public const RND_ROLE = 4;
    public const RND_ADMIN_ROLE = 5;
    public const FE_ROLE = 6;
    public const STAFF_ACCESS_CONTROL_ROLE = 7;
    public const CLOSER_ROLE = 8;
    public const TEAM_LEAD_ROLE = 9;
    public const RNA_SPECIALIST_ROLE = 10;
    public const CB_SPECIALIST_ROLE = 11;
    public const DECLINE_SPECIALIST_ROLE = 12;

    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
    public function getImageUrlattribute()
    {
        if ($this->image != null) {
            return asset('storage/' . $this->image);
        } else {
            return 'https://randomuser.me/api/portraits/men/85.jpg';
        }
    }
    public static function getRules($id = "", $role_id = '', $office_id = "")
    {
        $email_rule = ['required', 'email', Rule::unique('users')->ignore($id)];
        $rules = [
            'name' => ['required'],
            'role' => 'required',
            'password' => !$id ? 'required' : "",
        ];

        if (
            $role_id == self::AGENT_ROLE
            || $role_id == self::CLOSER_ROLE
            || $role_id == self::TEAM_LEAD_ROLE
            || $role_id == self::RNA_SPECIALIST_ROLE
            || $role_id == self::CB_SPECIALIST_ROLE
            || $role_id == self::DECLINE_SPECIALIST_ROLE
        ) {
            if ($office_id) {
                $rules['username'] = 'required';
                $rules['suffix'] = 'required';
            } else {
                $rules['email'] = $email_rule;
            }
            return $rules;
        } else {
            return [
                'name' => ['required'],
                'email' => $email_rule,
                'role' => 'required',
                'password' => !$id ? 'required' : "",
            ];
        }
    }
    public function office()
    {
        return $this->belongsTo(Offices::class);
    }
    public function setBirthDateAttribute($value)
    {
        $this->attributes['birth_date'] = ($value === 'null' || $value === null) ? null : Carbon::parse($value)->format('Y-m-d');
    }
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = str_replace('+92', '0', $value);
    }
    public function getNameAttribute($value)
    {
        if ($this->role_id == self::AGENT_ROLE && isset($this->office_id)) {
            $officeName = " (" . $this->office->name . ")";

            // Remove any existing occurrences of the office name
            $cleanedValue = preg_replace('/\s*\(' . preg_quote($this->office->name, '/') . '\)\s*/', '', $value);

            // Append the office name only once
            return trim($cleanedValue) . $officeName;
        }

        return $value;
    }

    // public function setNameAttribute($value)
    // {
    //     // Define a pattern to match and strip out anything in parentheses (e.g., " (Office Name)")
    //     $this->attributes['name'] = preg_replace('/\s*\(.*?\)\s*$/', '', trim($value));
    // }

    //------------------Relationships------------------
    public function notifications($limit = 20, $offset = null)
    {
        $user = Auth::user();

        $query = Notifications::where(function ($query) use ($user) {
            $query->where('receiver_id', $user->id)
                ->orWhere(function ($query) use ($user) {
                    $query->where('receiver_id', 0)
                        ->where('receiver_role', $user->role_id);
                });
        })->orderBy('created_at', 'desc');

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        if (!is_null($offset)) {
            $query->offset($offset);
        }

        $notifications = $query->get();

        return $notifications;
    }


    public function leads()
    {
        return $this->hasMany(Customer::class, 'agent_id');
    }

    public function familyMembers()
    {
        return $this->hasMany(UserFamilyMember::class);
    }

    // get scan reports of family members
    public function getScanReport()
    {
        return $this->hasMany(HealthScan::class);
    }

    public function healthScans()
    {
        return $this->hasMany(HealthScan::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function doctorReviews()
    {
        return $this->hasMany(Review::class);
    }

    public function doctorDetail()
    {
        return $this->hasOne(DoctorDetail::class, 'doctor_id');
    }

    public function doctorServices()
    {
        return $this->hasMany(DoctorService::class, 'doctor_id');
    }

    public function doctorServiceDetails()
    {
        return $this->hasManyThrough(Service::class, DoctorService::class, 'doctor_id', 'id', 'id', 'service_id');
    }

    public function doctorSpecialities()
    {
        return $this->hasMany(DoctorSpeciality::class, 'doctor_id');
    }

    public function doctorExperiences()
    {
        return $this->hasMany(DoctorExperience::class, 'doctor_id');
    }

    public function doctorSpecialityDetails()
    {
        return $this->hasManyThrough(Speciality::class, DoctorSpeciality::class, 'doctor_id', 'id', 'id', 'speciality_id');
    }

    public function doctorEducation()
    {
        return $this->hasMany(DoctorEducation::class, 'doctor_id');
    }

    public function subscription()
    {
        return $this->hasOne(UserSubscription::class)->where('status', true)->where('end_date', '>=', Carbon::now()->toDateTimeString())->with('package');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function newsletter()
    {
        return $this->hasOne(Newsletter::class, 'email', 'email');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function doctorClinics()
    {
        return $this->hasMany(DoctorClinic::class, 'doctor_id')->where('status', true)->whereHas('clinicTimings')->groupBy('clinic_id');
    }

    public function socialAccounts()
    {
        return $this->hasMany(UserSocialAccount::class, 'user_id');
    }

    public function appointment()
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

    public function doctorAppointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'approved_by')->where('status', true)->orderBy('updated_at', 'ASC');
    }

    public function hasDoctor()
    {
        return $this->belongsTo(User::class, 'id')
            ->where(['role_id' => 3, 'status' => true, 'is_blocked' => false])
            ->whereHas('doctorDetail')
            ->whereHas('doctorSpecialityDetails')
            ->whereHas('doctorServiceDetails');
    }

    public function doctorBankDetails()
    {
        return $this->hasOne(DoctorBankDetail::class, 'doctor_id');
    }

    public function doctorHighPaidClinic()
    {
        return $this->hasOne(DoctorClinic::class, 'doctor_id')->where('status', true)->whereHas('clinicTimings')->groupBy('clinic_id')->orderBy('consultation_fee', 'desc');
    }

    public function doctorLowPaidClinic()
    {
        return $this->hasOne(DoctorClinic::class, 'doctor_id')->where('status', true)->whereHas('clinicTimings')->groupBy('clinic_id')->orderBy('consultation_fee', 'asc');
    }

    public function fcm_tokens()
    {
        return $this->hasMany(Fcm_Tokens::class)->where('status', true);
    }
    //------------------Relationships Ends------------------

    /**
     * This method is used to get single User by ID or Email
     */
    public static function getUser($data, $otp = null, $password = null, $newPhone = null)
    {
        $user = User::where(['status' => true, 'is_blocked' => false]);
        if ($data && $otp) {
            return  $user->where(['otp' => $otp])->where(function ($query) use ($data) {
                $query->orWhere(['phone' => $data, 'email' => $data, 'id' => $data]);
            })->first(); // If user comes with phone and otp to verify
        }
        if ($data && $password) {
            return  $user->where(['email' => $data, 'password' => $password])->first(); // If user comes with email and password to verify
        }
        if ($data && $otp && $newPhone) {
            return $user->where(['id' => $data, 'otp' => $otp, 'new_phone' => $newPhone])->first(); // If user comes with new phone to verify it
        }
        return $user->where('id', $data)->orWhere('phone', $data)->orWhere('email', $data)->first(); // If user comes only with phone or id or email
    }
    public function chats()
    {
        return $this->hasMany(Chat::class, 'chat_id');
    }
    /**
     * This method is used to get all the doctors
     */
    public static function getAllDoctors(
        $userId,
        $roleId,
        $doctor = null,
        $city = null,
        $speciality = null,
        $service = null,
        $search = null,
        $isAppointment = null,
        $isFeatured = null,
        $availableNow = null,
        $byFees = null,
        $mostReviewed = null,
        $mostExperince = null
    ) {
        !$speciality ?? strtolower($speciality);
        $mostReviewed = $mostReviewed ? 'desc' : 'asc';
        $availableNow = $availableNow == 'true' ? [true] : [true, false];
        $getDoctors = User::where(['status' => true, 'is_blocked' => false, 'role_id' => $roleId])
            ->withCount('doctorReviews as review_count')
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->orWhere('name', 'like', "%$search%");
                    $query->orWhereHas('doctorClinics.clinic', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    });
                }
            })
            ->whereHas('city', function ($query) use ($city) {
                if ($city) {
                    $query->orWhere('name', 'like', "%$city%");
                    $query->orWhere('slug', 'like', "%$city%");
                }
            })
            ->whereHas('doctorSpecialityDetails', function ($query) use ($speciality) {
                if ($speciality) {
                    $query->orWhere('name', 'like', "%$speciality%");
                    $query->orWhere('slug', 'like', "%$speciality%");
                }
            })
            ->whereHas('doctorServiceDetails', function ($query) use ($service) {
                if ($service) {
                    $query->where('name', 'like', "%$service%");
                }
            })
            ->where(function ($query) use ($doctor, $isFeatured, $availableNow) {
                if ($doctor) {
                    $query->where('name', 'like', "%$doctor%");
                    $query->orWhereHas('doctorDetail', function ($query) use ($doctor) {
                        $query->where('prefix', 'like', "%$doctor%");
                    });
                }
                if ($availableNow !== null) {
                    $query->orWhereHas('doctorDetail', function ($query) use ($availableNow) {
                        $query->whereIn('is_available', $availableNow);
                    });
                }
                if ($isFeatured) {
                    $query->whereHas('doctorDetail', function ($query) use ($isFeatured) {
                        $query->whereIn('badge', ['gold']);
                    });
                }
            })
            ->where(function ($query) use ($isAppointment, $userId) {
                if ($isAppointment) {
                    $query->wherehas('doctorAppointments', function ($subQuery) use ($userId) {
                        $subQuery->where('user_id', $userId);
                    });
                }
            })
            ->orderBy('review_count', $mostReviewed)
            ->with('doctorDetail', 'doctorHighPaidClinic')
            ->get();

        if ($byFees) {
            $getDoctors = $getDoctors->sortByDesc('doctorHighPaidClinic.consultation_fee');
        }
        if ($mostExperince) {
            $getDoctors = $getDoctors->sortByDesc('doctorDetail.experience_year');
        }
        return $getDoctors;
    }
}
