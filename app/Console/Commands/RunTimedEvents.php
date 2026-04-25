<?php

namespace App\Console\Commands;

use App\Services\DateNotificationService;
use App\Services\Events\BirthdayInTwoDaysEvent;
use App\Services\Events\BirthdayTodayEvent;
use App\Services\Events\EventRunner;
use App\Services\Events\TdrProcessOverdueStartEvent;
use Illuminate\Console\Command;

class RunTimedEvents extends Command
{
    protected $signature = 'events:run';

    protected $description = 'Run timed business events (overdue, reminders, etc.)';

    public function handle(EventRunner $runner, DateNotificationService $dateNotificationService): int
    {
        $runner->run([
            new TdrProcessOverdueStartEvent(),
            new BirthdayInTwoDaysEvent(),
            new BirthdayTodayEvent(),
        ]);

        $dateNotificationService->sendDueForToday();

        return self::SUCCESS;
    }
}
