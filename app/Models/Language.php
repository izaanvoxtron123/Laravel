<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{    
    protected $table = 'languages';
    protected $guarded=['id'];
    protected $appends = ['e_id'];

    public const ENGLISH = 1;
    public const URDU = 2;

    public static function getValidationRules($id = ""){
        return [
            'name' => 'required',
            'slug' => 'required',
            'direction' => 'required',
        ];
    }
    
    public function getEIdAttribute(){
        return encrypt($this->id);
    }
}
