<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Auth;
use Carbon\Carbon;
use App\Models\Time;
use App\Models\Rest;
use App\Models\User;

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

        return view('index_checkout_breakin')->with('message','本日の勤務開始時間を登録しました');
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
        $existent_breakin = Rest::where('time_id',$existent_checkin->id)->latest()->first();
        if(!is_null($existent_breakin) && is_null($existent_breakin['breakOut'])) {
            return back()->with('alert','休憩終了ボタンを先に押してください');
        }
    
        Time::where('user_id', $existent_checkin['user_id'])->where('checkIn', $existent_checkin['checkIn'])->update(['checkOut' => Carbon::now()]);

        //勤務時間の算出と登録
        $latest_checkout = Time::where('user_id', $existent_checkin['user_id'])->where('checkIn', $existent_checkin['checkIn'])->first();
        $worktime = number_format((strtotime($latest_checkout['checkOut'])-strtotime($latest_checkout['checkIn']))/3600-$latest_checkout['breakTime']/60, 2);
        Time::where('id', $latest_checkout['id'])->update(['workTime' => $worktime]);

        return view('index_nothing')->with('message','本日の勤務終了時間を登録しました');    
    }
        
    //休憩開始
    public function breakin() {
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(!is_null($existent_checkin)) {
            $existent_breakin = Rest::where('time_id',$existent_checkin['id'])->latest()->first();
        }
        if(is_null($existent_checkin)) {
            return back()->with('alert','休憩開始ボタンは有効ではありません');
        } else {
            if (is_null($existent_checkin['checkOut']) && (is_null($existent_breakin)||!is_null($existent_breakin['breakOut']))) {
                $rest = Rest::create([
                    'time_id' => $existent_checkin->id,
                    'breakIn' => Carbon::now()
                ]);
                return view('index_breakout')->with('message', '休憩開始時間を登録しました');
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
            $existent_breakin = Rest::where('time_id',$existent_checkin['id'])->latest()->first();
        }
        if(is_null($existent_checkin)) {
            return back()->with('alert','休憩終了ボタンは有効ではありません');
        } else {
            if (is_null($existent_checkin['checkOut']) && !is_null($existent_breakin) && is_null($existent_breakin['breakOut'])) {
                Rest::where('time_id', $existent_breakin['time_id'])->where('breakIn', $existent_breakin['breakIn'])->update(['breakOut' => Carbon::now()]);

                //休憩時間の算出と登録
                $latestbreak = Rest::where('time_id', $existent_breakin['time_id'])->where('breakIn', $existent_breakin['breakIn'])->first();
                $latestbreaktime = intval((strtotime($latestbreak['breakOut'])-strtotime($latestbreak['breakIn']))/60);
                $existent_breaktime = Time::where('id', $latestbreak['time_id'])->first();
                $breaktime = $existent_breaktime['breakTime']+$latestbreaktime;
                Time::where('id', $latestbreak['time_id'])->update(['breakTime' => $breaktime]);

                return view('index_checkout_breakin')->with('message','休憩終了時間を登録しました');
            } else {
                return back()->with('alert','休憩終了ボタンは有効ではありません');
            }
        }

    }

    //勤怠実績表示
    public function record(Request $request) {
        $old_add = session()->get('add');
        $add = $request->add;
        $new_add = $old_add + $add;
        $request->session()->put('add', $new_add);
        $date = Carbon::yesterday()->addDay($new_add);
        $year = intval($date->year);
        $month = intval($date->month);
        $day = intval($date->day);

        if($date==Carbon::today()) {
            $date = $date->addDay(-1);
            $year = intval($date->year);
            $month = intval($date->month);
            $day = intval($date->day);
            $items = Time::where('year',$year)->where('month',$month)->where('day',$day)->get();
            foreach ($items as $item) {
            $user = User::Where('id',$item['user_id'])->first();
            $name = $user['name'];
            $item['user_id'] = $name;
            }
            $items = collect($items);
            $items = new LengthAwarePaginator(
            $items->forPage($request->page, 5),
            count($items),
            5,
            $request->page,
            array('path' => $request->url())
            );
            return view('attendance',['items'=>$items])->with('alert','本日以降の勤務実績は閲覧できません　→　＜ボタンを押してください');
        }
        elseif(!Time::where('year',$year)->where('month',$month)->where('day',$day)->exists()) {
            $date = $date->addDay(1);
            $year = intval($date->year);
            $month = intval($date->month);
            $day = intval($date->day);
            $items = Time::where('year',$year)->where('month',$month)->where('day',$day)->get();
            foreach ($items as $item) {
            $user = User::Where('id',$item['user_id'])->first();
            $name = $user['name'];
            $item['user_id'] = $name;
            }
            $items = collect($items);
            $items = new LengthAwarePaginator(
            $items->forPage($request->page, 5),
            count($items),
            5,
            $request->page,
            array('path' => $request->url())
            );
            return view('attendance',['items'=>$items])->with('alert','この日より前の勤務実績は閲覧できません　→　＞ボタンを押してください');
        }
        else {
            $items = Time::where('year',$year)->where('month',$month)->where('day',$day)->get();
            foreach ($items as $item) {
            $user = User::Where('id',$item['user_id'])->first();
            $name = $user['name'];
            $item['user_id'] = $name;
            }
            $items = collect($items);
            $items = new LengthAwarePaginator(
            $items->forPage($request->page, 5),
            count($items),
            5,
            $request->page,
            array('path' => $request->url())
            );
            return view('attendance',['items'=>$items]);
        }
    }
}
