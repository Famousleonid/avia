<?php

use App\Models\Workorder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $ids = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->whereNull('paint_queue_order')
            ->orderByDesc('number')
            ->pluck('id');

        foreach ($ids as $i => $id) {
            Workorder::whereKey($id)->update(['paint_queue_order' => $i]);
        }
    }

    public function down(): void
    {
        //
    }
};
