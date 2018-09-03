<?php

namespace Pterodactyl\Services\Schedules;

use DateTimeInterface;
use Cron\CronExpression;
use Cake\Chronos\Chronos;
use Pterodactyl\Models\Schedule;
use Cake\Chronos\ChronosInterface;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Jobs\Schedule\TestingBugJob;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ProcessScheduleService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \DateTimeInterface|null
     */
    private $runTimeOverride;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    private $scheduleRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * ProcessScheduleService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                      $connection
     * @param \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface $scheduleRepository
     * @param \Pterodactyl\Contracts\Repository\TaskRepositoryInterface     $taskRepository
     */
    public function __construct(ConnectionInterface $connection, ScheduleRepositoryInterface $scheduleRepository, TaskRepositoryInterface $taskRepository)
    {
        $this->connection = $connection;
        $this->scheduleRepository = $scheduleRepository;
        $this->taskRepository = $taskRepository;
    }

    /**
     * Set the time that this schedule should be run at. This will override the time
     * defined on the schedule itself. Useful for triggering one-off task runs.
     *
     * @param \DateTimeInterface $time
     * @return $this
     */
    public function setRunTimeOverride(DateTimeInterface $time)
    {
        $this->runTimeOverride = $time;

        return $this;
    }

    /**
     * Process a schedule and push the first task onto the queue worker.
     *
     * @param \Pterodactyl\Models\Schedule $schedule
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Schedule $schedule)
    {
        $this->scheduleRepository->loadTasks($schedule);

        /** @var \Pterodactyl\Models\Task $task */
        $task = $schedule->getRelation('tasks')->where('sequence_id', 1)->first();

        $formattedCron = sprintf('%s %s %s * %s',
            $schedule->cron_minute,
            $schedule->cron_hour,
            $schedule->cron_day_of_month,
            $schedule->cron_day_of_week
        );

        $this->connection->beginTransaction();
        $this->scheduleRepository->update($schedule->id, [
            'is_processing' => true,
            'next_run_at' => $this->getRunAtTime($formattedCron),
        ]);

        $this->taskRepository->update($task->id, ['is_queued' => true]);

        TestingBugJob::dispatch(1, 1)->delay(1);

        $this->connection->commit();
    }

    /**
     * Get the timestamp to store in the database as the next_run time for a schedule.
     *
     * @param string $formatted
     * @return \Cake\Chronos\ChronosInterface
     */
    private function getRunAtTime(string $formatted): ChronosInterface
    {
        if (! is_null($this->runTimeOverride)) {
            return $this->runTimeOverride instanceof ChronosInterface ? $this->runTimeOverride : Chronos::instance($this->runTimeOverride);
        }

        return Chronos::instance(CronExpression::factory($formatted)->getNextRunDate());
    }
}
