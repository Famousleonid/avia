<?php

namespace App\Console\Commands;

use App\Models\WoBushing;
use App\Services\BushingRouteBatches;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GroupCurrentBushingRoutes extends Command
{
    protected $signature = 'bushings:group-routes {--workorder= : Workorder database ID} {--all : Inspect all workorders} {--apply : Save changes; default is dry run}';
    protected $description = 'Automatically group current bushing routes while preserving all RO/started groups';

    public function handle(BushingRouteBatches $service): int
    {
        if (!$this->option('workorder') && !$this->option('all')) {
            $this->error('Specify --workorder=<ID> or --all.');
            return self::FAILURE;
        }
        $query = WoBushing::query()->when($this->option('workorder'), fn ($q, $id) => $q->where('workorder_id', $id));
        foreach ($query->orderBy('id')->cursor() as $bushing) {
            DB::beginTransaction();
            try {
                $service->rebuild($bushing);
                $this->line('WO ID '.$bushing->workorder_id.': '.json_encode($service->labels((int) $bushing->workorder_id)));
                $this->option('apply') ? DB::commit() : DB::rollBack();
            } catch (\Throwable $error) {
                DB::rollBack();
                throw $error;
            }
        }
        $this->info($this->option('apply') ? 'Saved.' : 'Dry run: no changes saved.');
        return self::SUCCESS;
    }
}
