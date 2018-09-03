<?php

namespace Pterodactyl\Jobs\Schedule;

use Pterodactyl\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TestingBugJob extends Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * @var int
     */
    public $schedule;

    /**
     * @var int
     */
    public $task;

    /**
     * RunTaskJob constructor.
     *
     * @param int $task
     * @param int $schedule
     */
    public function __construct(int $task, int $schedule)
    {
        $this->task = $task;
        $this->schedule = $schedule;
    }

    public function handle()
    {
        error_log('do nothing');
    }
}
