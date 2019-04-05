<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateJobIfEmpty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:truncate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate Jobs if Empty';

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
        $this->line("Truncating Jobs");
        $count = DB::table('jobs')->count();
        if ($count == 0){
            DB::table('jobs')->truncate();
            $this->line('Jobs not truncated due to not empty');
        } else {
            $this->line('Jobs is truncated');
        }
        DB::table('failed_jobs')->truncate();
        $this->line('Failed Jobs is successfully truncated');
    }
}
