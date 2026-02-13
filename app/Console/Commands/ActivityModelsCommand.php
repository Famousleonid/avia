<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Activitylog\Traits\LogsActivity;

class ActivityModelsCommand extends Command
{
    protected $signature = 'activity:models {--path=app/Models : Path to scan for models}';
    protected $description = 'List models that use Spatie LogsActivity and show their logging config';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (!is_dir($path)) {
            $this->error("Path not found: {$path}");
            return self::FAILURE;
        }

        $classes = $this->discoverClasses($path);

        $rows = [];
        foreach ($classes as $class) {
            if (!class_exists($class)) continue;

            // только Eloquent модели (примерно)
            if (!is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) continue;

            if (!in_array(LogsActivity::class, class_uses_recursive($class), true)) continue;

            $info = $this->extractActivityConfig($class);

            $rows[] = [
                'Model'              => $class,
                'Log name'           => $info['log_name'] ?? '',
                'Log only'           => $info['log_only'] ?? '',
                'Log except'         => $info['log_except'] ?? '',
                'Mode'               => $info['mode'] ?? '',
                'Only dirty'         => $info['log_only_dirty'] ?? '',
                'Dont empty'         => $info['dont_submit_empty_logs'] ?? '',
                'Record events'      => $info['record_events'] ?? '',
            ];
        }

        if (empty($rows)) {
            $this->warn('No models with LogsActivity were found.');
            return self::SUCCESS;
        }

        $this->table(array_keys($rows[0]), $rows);

        // Плюс: что реально есть в базе (subject_type)
        if (\Schema::hasTable('activity_log')) {
            try {
                $types = \DB::table('activity_log')
                    ->select('subject_type')
                    ->whereNotNull('subject_type')
                    ->distinct()
                    ->orderBy('subject_type')
                    ->pluck('subject_type')
                    ->toArray();

                $this->line('');
                $this->info('Distinct subject_type from activity_log:');
                foreach ($types as $t) $this->line(" - {$t}");
            } catch (\Throwable $e) {
                // молча
            }
        }

        return self::SUCCESS;
    }

    private function discoverClasses(string $path): array
    {
        $baseApp = base_path('app');

        $files = File::allFiles($path);

        $classes = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;

            $real = $file->getRealPath();
            if (!$real) continue;

            // превращаем путь app/.../Foo.php => App\...\Foo
            if (!Str::startsWith($real, $baseApp)) continue;

            $relative = Str::after($real, $baseApp . DIRECTORY_SEPARATOR);
            $relative = str_replace(['/', '\\'], '\\', $relative);
            $relative = Str::replaceLast('.php', '', $relative);

            $classes[] = 'App\\' . $relative;
        }

        sort($classes);
        return array_values(array_unique($classes));
    }

    private function extractActivityConfig(string $class): array
    {
        // Spatie хранит настройки в protected static свойствах на модели
        $rc = new ReflectionClass($class);

        $getStatic = function (string $prop) use ($rc) {
            if (!$rc->hasProperty($prop)) return null;
            $p = $rc->getProperty($prop);
            if (!$p->isStatic()) return null;
            $p->setAccessible(true);
            return $p->getValue();
        };

        $logName  = $getStatic('logName');
        $logOnly  = $getStatic('logAttributes');
        $logExcept = $getStatic('logExceptAttributes');

        $logUnguarded = (bool) ($getStatic('logUnguarded') ?? false);
        $logFillable  = (bool) ($getStatic('logFillable') ?? false);
        $logAll       = (bool) ($getStatic('logAll') ?? false);

        $mode = $logAll ? 'logAll' : ($logFillable ? 'logFillable' : ($logUnguarded ? 'logUnguarded' : 'logOnly'));

        $onlyDirty = (bool) ($getStatic('logOnlyDirty') ?? false);
        $dontEmpty = (bool) ($getStatic('submitEmptyLogs') ?? true) === false ? 'yes' : 'no';

        $recordEvents = $getStatic('recordEvents');
        if (is_array($recordEvents)) {
            $recordEvents = implode(',', $recordEvents);
        }

        return [
            'log_name' => $logName ?? '',
            'log_only' => is_array($logOnly) ? implode(',', $logOnly) : '',
            'log_except' => is_array($logExcept) ? implode(',', $logExcept) : '',
            'mode' => $mode,
            'log_only_dirty' => $onlyDirty ? 'yes' : 'no',
            'dont_submit_empty_logs' => $dontEmpty,
            'record_events' => $recordEvents ?? '',
        ];
    }
}
