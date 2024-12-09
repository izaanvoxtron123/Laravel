<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class NotificationPayloadCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
         $value = json_decode($value, true);
         $value['id'] = (String)$value['id'];
         return $value;
    }
    public function set($model, $key, $value, $attributes)
    {
        return $value;
    }
}