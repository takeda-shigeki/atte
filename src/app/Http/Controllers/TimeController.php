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
        //viewファイルの方で、勤務開始登録を1日に2度以上できないようになってはいるが、ここでも念のため
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(!is_null($existent_checkin)) {
            $existent_checkinDay = new Carbon($existent_checkin['check_in']);
            $existentDay = $existent_checkinDay->format('Y-m-d');
            $today = Carbon::today()->format('Y-m-d');
            if(($existentDay == $today)) {
                return back()->with('alert','勤務開始登録は1日1回までです');
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
            'check_in' => Carbon::now(),
        ]);

        return view('index_checkout_breakin')->with('message','本日の勤務開始時間を登録しました');
    }

    //勤務終了
    public function checkout() {
        //勤務開始登録をする前に勤務終了登録をできないようにする（viewファイルの方でボタンを押せないようにしているが、念のため）
        //同じ日に2回勤務終了登録をできないようにする（viewファイルの方でボタンを押せないようにしているが、念のため）
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(is_null($existent_checkin)) {
            return back()->with('alert','本日の勤務開始記録がありません');
        } else {
            $existent_checkinDay = new Carbon($existent_checkin['check_in']);
            $existentDay = $existent_checkinDay->format('Y-m-d');
            $today = Carbon::today()->format('Y-m-d');
            if(($existentDay != $today)) {
                return back()->with('alert','本日の勤務開始記録がありません');
            } else {
                if(!is_null($existent_checkin['check_out'])) {
                    return back()->with('alert','本日の勤務終了登録が既にされています');
                }
            }
        }

        //休憩終了登録をする前に勤務終了登録をできないようにする（viewファイルの方でボタンを押せないようにしているが、念のため）
        $existent_breakin = Rest::where('time_id',$existent_checkin->id)->latest()->first();
        if(!is_null($existent_breakin) && is_null($existent_breakin['break_out'])) {
            return back()->with('alert','休憩終了登録を先してください');
        }
    
        Time::where('user_id', $existent_checkin['user_id'])->where('check_in', $existent_checkin['check_in'])->update(['check_out' => Carbon::now()]);

        //勤務時間の算出と登録
        $latest_checkout = Time::where('user_id', $existent_checkin['user_id'])->where('check_in', $existent_checkin['check_in'])->first();
        $worktime = number_format((strtotime($latest_checkout['check_out'])-strtotime($latest_checkout['check_in']))/3600-$latest_checkout['break_time']/60, 2);
        Time::where('id', $latest_checkout['id'])->update(['work_time' => $worktime]);

        return view('index_nothing')->with('message','本日の勤務終了時間を登録しました');    
    }
        
    //休憩開始
    public function breakin() {
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(!is_null($existent_checkin)) {
            $existent_breakin = Rest::where('time_id',$existent_checkin['id'])->latest()->first();
        }
        //勤務開始登録をする前に休憩開始登録をできないようにする（viewファイルの方でボタンを押せないようにしているが、念のため）
        if(is_null($existent_checkin)) {
            return back()->with('alert','休憩開始ボタンは有効ではありません');
        } else {
            if (is_null($existent_checkin['check_out']) && (is_null($existent_breakin)||!is_null($existent_breakin['break_out']))) {
                $rest = Rest::create([
                    'time_id' => $existent_checkin->id,
                    'break_in' => Carbon::now()
                ]);
                return view('index_breakout')->with('message', '休憩開始時間を登録しました');
            }
            //勤務終了登録が既になされている、または、休憩開始登録をした後に休憩終了登録をしていない状態では、新たな休憩開始登録をできないようにする（viewファイルの方でボタンを押せないようにしているが、念のため）
            else {
                return back()->with('alert','休憩開始ボタンは有効ではありません');
            }
        }
    }

    //休憩終了
    public function breakout() {
        //休憩開始登録をした後に休憩終了登録をしていない状態でないと、休憩終了登録をできないようにする（viewファイルの方でボタンを押せないようにしているが、念のため）
        $user = Auth::user();
        $existent_checkin = Time::where('user_id',$user->id)->latest()->first();
        if(!is_null($existent_checkin)) {
            $existent_breakin = Rest::where('time_id',$existent_checkin['id'])->latest()->first();
        }
        if(is_null($existent_checkin)) {
            return back()->with('alert','休憩終了ボタンは有効ではありません');
        } else {
            if (is_null($existent_checkin['check_out']) && !is_null($existent_breakin) && is_null($existent_breakin['break_out'])) {
                Rest::where('time_id', $existent_breakin['time_id'])->where('break_in', $existent_breakin['break_in'])->update(['break_out' => Carbon::now()]);

                //休憩時間の算出と登録
                $latestbreak = Rest::where('time_id', $existent_breakin['time_id'])->where('break_in', $existent_breakin['break_in'])->first();
                $latestbreaktime = intval((strtotime($latestbreak['break_out'])-strtotime($latestbreak['break_in']))/60);
                $existent_breaktime = Time::where('id', $latestbreak['time_id'])->first();
                $breaktime = $existent_breaktime['break_time']+$latestbreaktime;
                Time::where('id', $latestbreak['time_id'])->update(['break_time' => $breaktime]);

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
        $next_record = Time::where('year',$year)->where('month',$month)->where('day',$day+1)->oldest()->first();

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
            return view('attendance',['items'=>$items, 'year'=>$year, 'month'=>$month, 'day'=>$day])->with('alert','本日以降の勤務実績は閲覧できません　→　＜ ボタンを押してください');
        }
        elseif(!is_null($next_record) && $next_record['id'] == 1) {
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
            return view('attendance',['items'=>$items, 'year'=>$year, 'month'=>$month, 'day'=>$day])->with('alert','この日より前の勤務実績は閲覧できません　→　＞ ボタンを押してください');
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
            return view('attendance',['items'=>$items, 'year'=>$year, 'month'=>$month, 'day'=>$day]);
        }
    }

    //ユーザー一覧表示
    public function userlist(Request $request) {
        $items = User::all();
        $items = collect($items);
        $items = new LengthAwarePaginator(
            $items->forPage($request->page, 5),
            count($items),
            5,
            $request->page,
            array('path' => $request->url())
            );
        return view('users',['items'=>$items]);
    }

    //ユーザー別勤怠実績
    public function eachuser(Request $request) {
        $userid = $request->userid;

        if(!is_null($userid)) {
            $request->session()->forget('userid');
            $request->session()->put('userid', $userid);
            $items = Time::where('user_id',$userid)->get();
            $user = User::Where('id',$userid)->first();
            $items = collect($items);
            $items = new LengthAwarePaginator(
                $items->forPage($request->page, 5),
                count($items),
                5,
                $request->page,
                array('path' => $request->url())
            );
            return view('eachuser_attendance',['items'=>$items, 'user'=>$user]);
        }
        else {
            $userid = session()->get('userid');
            $items = Time::where('user_id',$userid)->get();
            $user = User::Where('id',$userid)->first();
            $items = collect($items);
            $items = new LengthAwarePaginator(
                $items->forPage($request->page, 5),
                count($items),
                5,
                $request->page,
                array('path' => $request->url())
            );
            return view('eachuser_attendance',['items'=>$items, 'user'=>$user]);
        }
    }

}