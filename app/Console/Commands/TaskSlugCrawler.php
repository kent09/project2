<?php

namespace App\Console\Commands;

use App\Model\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TaskSlugCrawler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:slug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill empty task slug in database';

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
        //
        $this->taskNullSlugCrawler();
    }

    protected function taskNullSlugCrawler() {
        $tasks = Task::all();

        collect($tasks)->each(function($item) {
            if(!is_null($item->slug)) {
                $item->slug = NULL;
                if( $item->save() )
                    $this->info("slug set to null");
            }
        });

        if( collect($tasks)->count() > 0 ) {
            $tasks->each( function($item) use (& $list_same_slug) {

                if( is_null($item->slug) ) {
                    $set_slug = Str::slug($item->title);

                    $slug = Task::where(function($query) use ($set_slug) {
                        $query->where('slug', $set_slug);
                    })->first();

                    if( !$slug) {
                        $item->slug = $set_slug;
                        if( $item->save() )
                            $this->info("Slug [$item->slug] set to this task id [$item->id]");
                    } else {
                        $append_ext = base_convert( hash('md5', $item->id), 20, 36 );
                        $slug = Str::slug($item->title) . '.' . $append_ext;

                        $item->slug = $slug;
                        if( $item->save() )
                            $this->info("Slug [$item->slug] set to this task id [$item->id]");
                    }
                }
            });
        }
    }
}
