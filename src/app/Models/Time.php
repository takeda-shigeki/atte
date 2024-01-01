<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'year', 'month', 'day', 'checkIn', 'checkOut', 'breakIn', 'breakOut', 'workTime'];

    public function user()
    {
        $this->belongsTo(Auth::user());
    }

    public function scopeGetYearAttendance($query,$year) {
        return $query->where('year',$year);
    }
    public function scopeGetMonthAttendance($query,$month) {
        return $query->where('month',$month);
    }
    public function scopeGetDayAttendance($query,$day) {
        return $query->where('day',$day);
    }
}
