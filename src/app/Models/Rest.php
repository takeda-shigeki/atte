<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'time_id', 'breakIn', 'breakOut'];

    public function time()
    {
        $this->belongsTo(Auth::user());
        $this->belongsTo(Time::class);
    }
}
