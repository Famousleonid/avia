<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $groups = DB::table('general_tasks')->whereNull('catalog_removed_at')
                ->where('technician_stage', 'disassembly')->pluck('id');
            foreach ($groups as $groupId) {
                $tasks = DB::table('tasks')->where('general_task_id', $groupId)
                    ->whereNull('archived_at')->lockForUpdate()->get();
                // The previous production cutover reused these legacy definitions.
                // New installations already have separate unprefixed definitions.
                $legacy = $tasks->filter(fn ($task) => preg_match('/^\d+[.)]\s*WO Submitted to inspection$/i', trim($task->name)));
                if ($legacy->isEmpty()) continue;
                $names = $tasks->map(fn ($task) => strtolower(trim($task->name)));
                if (!$names->contains('wait records') || !$names->contains('in quotation')) {
                    throw new RuntimeException('Apply the reviewed Work Stage catalogue cutover SQL before retiring the reused legacy Disassembly tasks.');
                }

                foreach ($tasks as $task) {
                    $name = preg_replace('/^\d+[.)]\s*/', '', trim($task->name));
                    if (!$legacy->contains('id', $task->id) && strtolower($name) !== 'post disassembly inspection') continue;
                    DB::table('tasks')->where('id', $task->id)->update(['archived_at' => now()]);
                    if (!DB::table('tasks')->where('general_task_id', $groupId)->whereNull('archived_at')->where('name', $name)->exists()) {
                        DB::table('tasks')->insert([
                            'general_task_id' => $groupId, 'name' => $name,
                            'task_has_start_date' => false, 'sort_order' => $task->sort_order,
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Deliberately retain both generations: new tasks may already have WO dates.
    }
};
