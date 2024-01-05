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
    public function handle()
    {
        $today = Carbon::today();
        $year = intval($today->year);
        $month = intval($today->month);
        $day = intval($today->day);
        $records = Time::where('year',$year)->where('month',$month)->where('day',$day)->whereNotNull('checkIn')->whereNull('checkOut')->get();
        foreach ($records as $record) {
            Time::where('id', $record['id'])->update(['checkOut' => Carbon::now()]);
        }
    }
}
