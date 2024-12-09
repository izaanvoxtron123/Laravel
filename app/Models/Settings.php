<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;
    protected $table = 'settings';
    protected $guarded = ['id'];

        // To get multiple configuration values
        static public function getValues($params) {
            $rows = Settings::whereIn('key',$params)->get(['key','value']);
            $data = [];
            foreach($rows as $r) {
                $data[$r['key']] = $r['value'];
            }
            return $data;
        }
    
        // To get single configuration value
        static public function getValue($param) {
            $row = Settings::where('key',$param)->first(['key','value']);
            if($row->value){
                return $row->value;
            }else{
                return 0;
            }
        }
    
}
