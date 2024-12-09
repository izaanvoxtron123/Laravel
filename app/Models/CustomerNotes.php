<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerNotes extends Model
{
    use HasFactory;
    protected $table = 'customer_notes';
    protected $guarded = ['id'];



    public static function getValidationRules($id = "")
    {
        return [
            'note' => 'required',
            'author_id' => 'required',
            'customer_id' => 'required',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

}
