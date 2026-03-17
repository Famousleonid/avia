<?php
// app/Console/Commands/RunTimedEvents.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Events\EventRunner;
use App\Services\Events\TdrProcessOverdueStartEvent;
use App\Services\Events\BirthdayAdminEvent;
use App\Services\Events\BirthdaySelfEvent;
use Illuminate\Support\Facades\Log;

class RunTimedEvents extends Command
{
    protected $signature = 'events:run';
    protected $description = 'Run timed business events (overdue, reminders, etc.)';

    public function handle(EventRunner $runner): int
    {
        $runner->run([
            new TdrProcessOverdueStartEvent(),
            new BirthdaySelfEvent(),
            new BirthdayAdminEvent(),
            // потом добавишь новые события сюда


        ]);
       // Log::channel('avia')->info('RunTimedEvents started');
        return self::SUCCESS;
    }
}
