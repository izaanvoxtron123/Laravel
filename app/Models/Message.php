<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $appends = ['file_url','file_type'];
    protected $fillable = [
        'message',
        'from_id',
        'to_id',
        'chat_id',
        'is_readed',
        'file',
    ];
    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }
    public function to()
    {
        return $this->belongsTo(User::class, 'to_id');
    }
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }
    public function getFileUrlAttribute()
    {
        if ($this->file != null) {
            return asset('storage/' . $this->file);
        } else {
            return null;
        }
    }
    public function getFileTypeAttribute()
    {
        if ($this->file != null) {
            $exploded = explode('.', $this->file);
            $file_extension = end($exploded);

            // Check for supported file types
            if (in_array($file_extension, ['mp4', 'mov'])) {
                return "<video class='media-message' style='width: 200px;' controls><source src='" . asset('storage/' . $this->file) . "' type='video/mp4'></video>";
            } 
            elseif (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                return "<img class='media-message' width='100px' height='100px' src='" . asset('storage/' . $this->file) . "' alt='Image'>";
            }
            elseif (in_array($file_extension, ['xls','csv','pdf'])) {
                return "<a href='" . asset('storage/' . $this->file) . "' target='__blank'>Open File</a>";
            }
        }
        return null;
    }

}