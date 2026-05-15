<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    public function up(): void
    {
        Media::query()
            ->where('model_type', App\Models\Manual::class)
            ->where('collection_name', 'csv_files')
            ->get()
            ->each
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
