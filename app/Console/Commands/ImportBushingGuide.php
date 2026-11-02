<?php

namespace App\Console\Commands;

use App\Models\{Manual, ManualBushingGuide, User};
use App\Services\BushingGuideWorkbook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportBushingGuide extends Command
{
    protected $signature = 'bushing-guide:import {file} {--actor= : Existing user ID or email}';
    protected $description = 'Import Items and Bushing once as a shared ERJ 190/195 manual guide';

    public function handle(BushingGuideWorkbook $reader): int
    {
        $actor = User::where('email', $this->option('actor'))->first();
        if (!$actor && ctype_digit((string) $this->option('actor'))) $actor = User::find($this->option('actor'));
        if (!$actor) { $this->error('An existing --actor user is required.'); return self::FAILURE; }
        $manuals = Manual::whereIn('number', ['32-11-14RM', '32-11-15RM'])->get();
        if ($manuals->count() !== 2) { $this->error('Both target manuals must exist exactly once.'); return self::FAILURE; }
        if (ManualBushingGuide::where('key', 'erj-190-195-bushing')->exists()) {
            $this->error('Guide already exists. Edit the shared form; import never overwrites saved changes.');
            return self::FAILURE;
        }
        $document = $reader->read($this->argument('file'));
        $guide = DB::transaction(function () use ($document, $manuals, $actor) {
            $guide = ManualBushingGuide::create(['key' => 'erj-190-195-bushing', 'title' => 'ERJ 190/195 Buching', 'document' => $document, 'revision' => 1]);
            $guide->manuals()->sync($manuals->modelKeys());
            DB::table('manual_bushing_guide_changes')->insert([
                'guide_id' => $guide->id, 'user_id' => $actor->id, 'user_name' => $actor->name,
                'revision' => 1, 'event' => 'imported', 'created_at' => now(),
                'changes' => json_encode(['source' => $document['source_name'], 'sha256' => $document['source_sha256'], 'sheets' => ['Items', 'Bushing']], JSON_UNESCAPED_UNICODE),
            ]);
            return $guide;
        });
        $this->info('Imported guide '.$guide->id.'; Items and Bushing linked to '.$manuals->pluck('number')->implode(', '));
        return self::SUCCESS;
    }
}
