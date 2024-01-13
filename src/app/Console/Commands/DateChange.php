<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Time;
use Carbon\Carbon;

class DateChange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dateChange';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '日付変更';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $today = Carbon::today();
        $year = intval($today->year);
        $month = intval($today->month);
        $day = intval($today->day);
        $records = Time::where('year',$year)->where('month',$month)->where('day',$day)->whereNotNull('checkIn')->whereNull('checkOut')->get();
        foreach ($records as $record) {
            $break_record = Rest::where('time_id', $record['id'])->latest()->first();
            if(!is_null($break_record) && !is_null($break_record['breakIn']) && is_null($break_record['breakOut'])) {
                Rest::where('id', $break_record['id'])->update(['breakOut' => Carbon::now()]);

                //休憩時間の算出と登録
                $latestbreak = Rest::where('id', $break_record['id'])->first();
                $latestbreaktime = intval((strtotime($latestbreak['breakOut'])-strtotime($latestbreak['breakIn']))/60);
                $existent_breaktime = Time::where('id', $latestbreak['time_id'])->first();
                $breaktime = $existent_breaktime['breakTime']+$latestbreaktime;
                Time::where('id', $latestbreak['time_id'])->update(['breakTime' => $breaktime]);
                }

            Time::where('id', $record['id'])->update(['checkOut' => Carbon::now()]);

            //勤務時間の算出と登録
            $latest_checkout = Time::where('id', $record['id'])->first();
            $worktime = number_format((strtotime($latest_checkout['checkOut'])-strtotime($latest_checkout['checkIn']))/3600-$latest_checkout['breakTime']/60, 2);
            Time::where('id', $latest_checkout['id'])->update(['workTime' => $worktime]);

            $tomorrow = Carbon::tomorrow();
            Time::create([
                'user_id' => $record->user_id,
                'year' => intval($tomorrow->year),
                'month' => intval($tomorrow->month),
                'day' => intval($tomorrow->day),
                'checkIn' => $tomorrow,
            ]);

            $tomorrow_time = Time::where('user_id', $record['user_id'])->where('checkIn', $tomorrow)->first();
            Rest::create([
                'time_id' => $tomorrow_time->id,
                'breakIn' => $tomorrow
            ]);
        }
    }
}
