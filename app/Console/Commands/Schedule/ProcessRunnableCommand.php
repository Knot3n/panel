<?php

namespace Pterodactyl\Console\Commands\Schedule;

use Cake\Chronos\Chronos;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessRunnableCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Process schedules in the database and determine which are ready to run.';

    /**
     * @var \Pterodactyl\Services\Schedules\ProcessScheduleService
     */
    protected $processScheduleService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var string
     */
    protected $signature = 'p:schedule:process';

    /**
     * ProcessRunnableCommand constructor.
     *
     * @param \Pterodactyl\Services\Schedules\ProcessScheduleService        $processScheduleService
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $repository
     */
    public function __construct(ProcessScheduleService $processScheduleService, ScheduleRepositoryInterface $repository)
    {
        parent::__construct();

        Log::info('Constructed process runnable.');
        $this->processScheduleService = $processScheduleService;
        $this->repository = $repository;
    }

    /**
     * Handle command execution.
     */
    public function handle()
    {
        Log::info('Calling process runnable handle');
        $schedules = $this->repository->getSchedulesToProcess(Chronos::now()->toAtomString());
        if (empty($schedules)) {
            $this->line('No schedules set to be processed.');

            return;
        }

        $bar = $this->output->createProgressBar(count($schedules));
        $schedules->each(function ($schedule) use ($bar) {
            if ($schedule->tasks instanceof Collection && count($schedule->tasks) > 0) {
                $this->processScheduleService->handle($schedule);

                if ($this->input->isInteractive()) {
                    $bar->clear();
                    $this->line(trans('command/messages.schedule.output_line', [
                        'schedule' => $schedule->name,
                        'hash' => $schedule->hashid,
                    ]));
                }
            }

            $bar->advance();
            $bar->display();
        });

        $this->line('');
    }
}
