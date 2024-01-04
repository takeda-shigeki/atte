<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use App\Models\Time;
use App\Models\Rest;

class TimeController extends Controller
{
    //勤務開始
    public function checkin() {
        //同じ日に2回勤務開始ボタンを押せないようにする
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(!is_null($existent_checkin)) {
            $existent_checkinDay = new Carbon($existent_checkin['checkIn']);
            $existentDay = $existent_checkinDay->format('Y-m-d');
            $today = Carbon::today()->format('Y-m-d');
            if(($existentDay == $today)) {
                return back()->with('alert','勤務開始ボタンは既に押されています');
            }
        }

        $today = Carbon::today();
        $year = intval($today->year);
        $month = intval($today->month);
        $day = intval($today->day);
        $user = Auth::user();
        $time = Time::create([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'checkIn' => Carbon::now(),
        ]);

        return redirect('/')->with('message','本日の勤務開始時間を登録しました');
    }

    //勤務終了
    public function checkout() {
        //勤務開始ボタンを押す前に勤務終了ボタンを押せないようにする
        //同じ日に2回勤務終了ボタンを押せないようにする
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(is_null($existent_checkin)) {
            return back()->with('alert','本日の勤務開始記録がありません');
        } else {
            $existent_checkinDay = new Carbon($existent_checkin['checkIn']);
            $existentDay = $existent_checkinDay->format('Y-m-d');
            $today = Carbon::today()->format('Y-m-d');
            if(($existentDay != $today)) {
                return back()->with('alert','本日の勤務開始記録がありません');
            } else {
                if(!is_null($existent_checkin['checkOut'])) {
                    return back()->with('alert','勤務終了ボタンは既に押されています');
                }
            }
        }

        //休憩終了ボタンを押す前に勤務終了ボタンを押せないようにする
        $existent_breakin = Rest::where('user_id',$user->id)->where('time_id',$existent_checkin->id)->latest()->first();
        if(!is_null($existent_breakin) && is_null($existent_breakin['breakOut'])) {
            return back()->with('alert','休憩終了ボタンを先に押してください');
        }
    
        Time::where('user_id', $existent_checkin['user_id'])->where('checkIn', $existent_checkin['checkIn'])->update(['checkOut' => Carbon::now()]);
        return redirect('/')->with('message','本日の勤務終了時間を登録しました');    
    }
        
    //休憩開始
    public function breakin() {
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(!is_null($existent_checkin)) {
            $existent_breakin = Rest::where('user_id',$user->id)->where('time_id',$existent_checkin['id'])->latest()->first();
        }
        if(is_null($existent_checkin)) {
            return back()->with('alert','休憩開始ボタンは有効ではありません');
        } else {
            if (is_null($existent_checkin['checkOut']) && (is_null($existent_breakin)||!is_null($existent_breakin['breakOut']))) {
                $rest = Rest::create([
                    'user_id' => $user->id,
                    'time_id' => $existent_checkin->id,
                    'breakIn' => Carbon::now()
                ]);
                return redirect('/')->with('message','休憩開始時間を登録しました');
            } else {
                return back()->with('alert','休憩開始ボタンは有効ではありません');
            }
        }
    }

    //休憩終了
    public function breakout() {
        //休憩開始ボタンが押された状態でないと休憩終了ボタンを押せないようにする
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(!is_null($existent_checkin)) {
            $existent_breakin = Rest::where('user_id',$user->id)->where('time_id',$existent_checkin['id'])->latest()->first();
        }
        if(is_null($existent_checkin)) {
            return back()->with('alert','休憩終了ボタンは有効ではありません');
        } else {
            if (is_null($existent_checkin['checkOut']) && !is_null($existent_breakin) && is_null($existent_breakin['breakOut'])) {
                Rest::where('user_id', $existent_breakin['user_id'])->where('breakIn', $existent_breakin['breakIn'])->update(['breakOut' => Carbon::now()]);
                return redirect('/')->with('message','休憩開始終了を登録しました');
            } else {
                return back()->with('alert','休憩終了ボタンは有効ではありません');
            }
        }
    }

    //勤怠実績
    public function performance() {
        $items = [];
        return view('time.performance',['itmes'=>$items]);
    }
    public function result(Request $request) {
        $user = Auth::user();
        $items = Time::where('user_id',$user->id)->where('year',$request->year)->where('month',$request->month)->get();
        return view('time.performance',['itmes'=>$items]);
    }
}
