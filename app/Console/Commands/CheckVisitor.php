<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\VisitorCounter;
use Carbon\Carbon;


class CheckVisitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitor:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checking and Updating Visitors Records';

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
     * @return mixed
     */
    public function handle()
    {
        $this->line(Carbon::now());
        $this->line('Checking records...');
        $now = Carbon::now();
        $limit = $now->subMinutes(5);
        $visitors = VisitorCounter::where('status', 1)->where('updated_at', '<', $limit)->get(['id']);
        foreach ($visitors as $visitor) {
            $row = VisitorCounter::find($visitor->id);
            $row->status = 0;
            $row->save();
        }
        $this->info('Done Checking');
    }
}
