<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use App\Models\Time;
use App\Models\Rest;

class AuthController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        //同じ日に既に勤務開始ボタンを押していないか確認する
        if(!is_null($existent_checkin)) {
            $existent_checkinDay = new Carbon($existent_checkin['check_in']);
            $existentDay = $existent_checkinDay->format('Y-m-d');
            $today = Carbon::today()->format('Y-m-d');
            if(($existentDay == $today)) {
                $existent_breakin = Rest::where('time_id',$existent_checkin['id'])->latest()->first();
                //同じ日に既に勤務終了ボタンを押していないか確認する
                if (!is_null($existent_checkin['check_out'])) {
                    return view('index_nothing');
                }
                //休憩開始ボタンを押して、休憩終了ボタンをまだ押していない状態でないかを確認する
                else{
                    if(!is_null($existent_breakin)) {
                        if (!is_null($existent_breakin['break_in']) && is_null($existent_breakin['break_out'])) {
                            return view('index_breakout');
                        } elseif (!is_null($existent_breakin['break_in']) && !is_null($existent_breakin['break_out'])) {
                            return view('index_checkout_breakin');
                        }
                    }
                    else {
                        return view('index_checkout_breakin');
                    }
                }
            }
            else {
                return view('index_checkin');
                }
        }
        else {
            return view('index_checkin');
            }
    }
}
