<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'year', 'month', 'day', 'checkIn', 'checkOut', 'breakTime', 'workTime'];

    public function user()
    {
        $this->belongsTo(Auth::user());
    }
}
