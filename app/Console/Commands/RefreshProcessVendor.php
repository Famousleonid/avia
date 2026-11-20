<?php

namespace App\Console\Commands;

use App\Services\ProcessVendorCache;
use Illuminate\Console\Command;

class RefreshProcessVendor extends Command
{
    protected $signature = 'process-vendor:refresh {--force : Rebuild all candidate workorders} {--watch : Keep refreshing locally every 5 minutes}';
    protected $description = 'Refresh the precomputed Process Vendor queue; rebuild only changed workorders';

    public function handle(ProcessVendorCache $cache): int
    {
        do {
            $start = microtime(true);
            $result = $cache->refresh((bool) $this->option('force'));
            $this->info(json_encode($result + ['seconds' => round(microtime(true) - $start, 2)]));
            if ($this->option('watch')) sleep(300);
        } while ($this->option('watch'));
        return self::SUCCESS;
    }
}
