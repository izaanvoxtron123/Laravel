<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerExports extends Model
{
    use HasFactory;
    protected $table = 'customer_exports';
    protected $guarded = ['id'];
    protected $appends = ['e_id'];

    public function getEIdAttribute()
    {
        return encrypt($this->id);
    }
}
