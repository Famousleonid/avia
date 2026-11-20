<?php

namespace Tests\Feature;

use App\Models\{ProcessName, Tdr, TdrProcess};
use App\Services\ProcessPrintAttachment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\{BuildsDomainData, TestCase};

class ProcessPrintAttachmentTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function image(string $name): UploadedFile
    {
        $path = base_path('codex-test-runtime/'.$name);
        $image = imagecreatetruecolor(800, 1200);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);
        imagerectangle($image, 8, 8, 791, 1191, $black);
        imagestring($image, 5, 30, 30, 'PRINT ATTACHMENT - TOP', $black);
        imagestring($image, 5, 30, 1150, 'BOTTOM - MUST BE VISIBLE', $black);
        imagepng($image, $path);
        imagedestroy($image);
        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function invalidFile(): UploadedFile
    {
        $path = base_path('codex-test-runtime/invalid-attachment.pdf');
        file_put_contents($path, '%PDF-test');
        return new UploadedFile($path, 'invalid.pdf', 'application/pdf', null, true);
    }

    public function test_manager_upload_replace_delete_are_scoped_validated_and_audited(): void
    {
        Storage::fake('private');
        $manager = $this->createUserWithRole('Manager');
        $this->actingAs($manager);
        $process = ProcessName::create(['name'=>'Attachment test', 'process_sheet_name'=>'NDT']);
        $other = ProcessName::create(['name'=>'Other attachment test', 'process_sheet_name'=>'NDT']);
        $manual = $this->createManual();
        $url = route('process-print-attachments.store', $process);
        $this->get(route('process-print-attachments.index', $process))->assertOk();
        $this->postJson($url, ['manual_id'=>$manual->id, 'part_number'=>'  abc-12R ', 'image'=>$this->image('drawing.png')])->assertOk();
        $first = $process->fresh()->printAttachmentFor('ABC-12r', $manual->id);
        $this->assertNotNull($first);
        $this->get(route('process-print-attachments.image', [$process, $first]))->assertOk();
        $this->get(route('process-print-attachments.image', [$other, $first]))->assertNotFound();
        $this->deleteJson(route('process-print-attachments.destroy', [$other, $first]))->assertNotFound();
        $this->postJson($url, ['manual_id'=>$manual->id, 'part_number'=>'ABC-12R', 'image'=>$this->invalidFile()])->assertUnprocessable();
        $this->assertNotNull($process->fresh()->printAttachmentFor('ABC-12R', $manual->id));
        $this->postJson($url, ['manual_id'=>$manual->id, 'part_number'=>'ABC-12R', 'image'=>$this->image('replacement.png')])->assertOk();
        $second = $process->fresh()->printAttachmentFor('ABC-12R', $manual->id);
        $this->assertNotEquals($first->id, $second->id);
        $this->assertCount(1, $process->fresh()->getMedia(ProcessName::PRINT_ATTACHMENT_COLLECTION));
        $this->assertFalse(is_file($first->getPath()));
        $this->assertNull($process->fresh()->printAttachmentFor('ABC-12', $manual->id));
        $tech = $this->createUserWithRole('Technician');
        $this->actingAs($tech)->withSession(['password_hash_web'=>$tech->getAuthPassword()]);
        $this->get(route('process-print-attachments.index', $process))->assertForbidden();
        $this->postJson($url, [])->assertForbidden();
        $this->deleteJson(route('process-print-attachments.destroy', [$process, $second]))->assertForbidden();
        $this->patchJson(route('process-print-attachments.assign-manual', [$process, $second]), ['manual_id'=>$manual->id])->assertForbidden();
        $this->get(route('process-print-attachments.image', [$process, $second]))->assertOk();
        $this->actingAs($manager)->withSession(['password_hash_web'=>$manager->getAuthPassword()]);
        $this->deleteJson(route('process-print-attachments.destroy', [$process, $second]))->assertOk();
        $this->assertNull($process->fresh()->printAttachmentFor('ABC-12R', $manual->id));
        $logs = Activity::where('subject_type', ProcessName::class)->where('subject_id', $process->id)
            ->whereIn('description', ['Print attachment added', 'Print attachment replaced', 'Print attachment deleted'])->get();
        $this->assertCount(3, $logs);
        $this->assertTrue($logs->every(fn ($log) => $log->causer_id === $manager->id));
    }

    public function test_manual_isolation_legacy_assignment_and_conflicts_preserve_files(): void
    {
        Storage::fake('private');
        $this->actingAs($this->createUserWithRole('Manager'));
        $a = $this->createManual();
        $b = $this->createManual();
        $process = ProcessName::create(['name' => 'Scoped CAD', 'process_sheet_name' => 'CAD']);
        $legacy = $process->addMedia($this->image('legacy.png'))
            ->withCustomProperties(['part_number' => 'SAME-PN', 'part_number_key' => 'SAME-PN'])
            ->toMediaCollection(ProcessName::PRINT_ATTACHMENT_COLLECTION);
        $this->assertNull($process->fresh()->printAttachmentFor('SAME-PN', $a->id));
        $this->assertNull($process->fresh()->printAttachmentFor('SAME-PN'));
        $this->get(route('process-print-attachments.index', $process))->assertSee('Manual not assigned');
        $upload = route('process-print-attachments.store', $process);
        $this->postJson($upload, ['part_number'=>'SAME-PN', 'image'=>$this->image('missing-manual.png')])->assertUnprocessable();
        $this->postJson($upload, ['manual_id'=>$a->id, 'part_number'=>'SAME-PN', 'image'=>$this->image('a.png')])->assertOk();
        $this->patchJson(route('process-print-attachments.assign-manual', [$process, $legacy]), ['manual_id'=>$a->id])->assertConflict();
        $this->assertFileExists($legacy->getPath());
        $this->assertNull($legacy->fresh()->getCustomProperty('manual_id'));
        $this->patchJson(route('process-print-attachments.assign-manual', [$process, $legacy]), ['manual_id'=>$b->id])->assertOk();
        $this->assertSame($legacy->id, $process->fresh()->printAttachmentFor('same-pn', $b->id)->id);
        $aImage = $process->fresh()->printAttachmentFor('SAME-PN', $a->id);
        $this->assertNotEquals($legacy->id, $aImage->id);
        $service = app(ProcessPrintAttachment::class);
        $this->assertNull($service->forForm($process->fresh(), [], [
            ['component'=>(object)['part_number'=>'SAME-PN', 'manual_id'=>$a->id]],
            ['component'=>(object)['part_number'=>'SAME-PN', 'manual_id'=>$b->id]],
        ]));
        $this->deleteJson(route('process-print-attachments.destroy', [$process, $aImage]))->assertOk();
        $this->assertFileExists($legacy->getPath());
        $this->assertNotNull($process->fresh()->printAttachmentFor('SAME-PN', $b->id));
    }

    public function test_tdr_upload_uses_part_manual_instead_of_wo_or_request_manual(): void
    {
        Storage::fake('private');
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder();
        $manual = $this->createManual();
        $part = $this->createComponent($manual);
        $tdr = Tdr::create(['workorder_id'=>$wo->id, 'component_id'=>$part->id, 'qty'=>1]);
        $process = ProcessName::create(['name'=>'Scoped paint', 'process_sheet_name'=>'PAINT']);
        $this->get(route('process-print-attachments.index', [$process, 'tdr_id'=>$tdr->id]))
            ->assertOk()->assertSee($manual->number)->assertSee($part->part_number);
        $this->postJson(route('process-print-attachments.store', $process), [
            'tdr_id'=>$tdr->id, 'manual_id'=>$wo->unit->manual_id, 'part_number'=>'FORGED',
            'image'=>$this->image('tdr.png'),
        ])->assertOk();
        $image = $process->fresh()->printAttachmentFor($part->part_number, $manual->id);
        $this->assertNotNull($image);
        $this->assertNull($process->fresh()->printAttachmentFor('FORGED', $wo->unit->manual_id));
        $this->assertSame(0, $tdr->tdrProcesses()->count());
        $row = TdrProcess::create(['tdrs_id'=>$tdr->id, 'process_names_id'=>$process->id, 'processes'=>[]]);
        $this->assertSame($image->id, app(ProcessPrintAttachment::class)->forForm($process->fresh(), [$row])['media']->id);
        $this->get(route('tdr-processes.attachments', [$tdr, 'row'=>$row->id]))
            ->assertOk()->assertSee(route('process-print-attachments.image', [$process, $image]), false);
    }

    public function test_single_part_form_appends_only_matching_image_and_preserves_suffix(): void
    {
        Storage::fake('private');
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder();
        $manual = $wo->unit->manual;
        $component = $this->createComponent($wo->unit->manual);
        $component->update(['manual_id'=>$manual->id, 'part_number'=>'ATTACH-12R']);
        $tdr = Tdr::create(['workorder_id'=>$wo->id, 'component_id'=>$component->id, 'qty'=>1]);
        $name = ProcessName::create(['name'=>'US Measurement', 'process_sheet_name'=>'NDT', 'form_number'=>'016', 'print_form'=>true]);
        $row = TdrProcess::create(['tdrs_id'=>$tdr->id, 'process_names_id'=>$name->id, 'processes'=>[]]);
        $this->postJson(route('process-print-attachments.store', $name), [
            'manual_id'=>$manual->id, 'part_number'=>'attach-12r', 'image'=>$this->image('diagram.png'),
        ])->assertOk();
        $media = $name->fresh()->printAttachmentFor('ATTACH-12R', $manual->id);
        $url = route('tdr-processes.show', $row);
        $html = $this->get($url)->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<section class="process-part-attachment"'));
        $this->assertStringContainsString('Part No: ATTACH-12R', $html);
        $this->assertStringContainsString('W'.$wo->number, $html);
        $this->assertStringContainsString('system-print-qr__code', $html);
        $html = str_replace(route('process-print-attachments.image', [$name, $media]), 'data:image/png;base64,'.base64_encode(file_get_contents($media->getPath())), $html);
        $html = str_replace('<link rel="stylesheet" href="'.asset('assets/Bootstrap 5/bootstrap.min.css').'">', '<style>'.file_get_contents(public_path('assets/Bootstrap 5/bootstrap.min.css')).'</style>', $html);
        file_put_contents(base_path('codex-test-runtime/process-attachment-form.html'), $html);
        $component->update(['manual_id'=>$manual->id, 'part_number'=>'ATTACH-12']);
        $this->assertStringNotContainsString('<section class="process-part-attachment"', $this->get($url)->assertOk()->getContent());
        $this->assertNull(app(ProcessPrintAttachment::class)->forForm($name->fresh(), [], [
            ['component'=>(object)['manual_id'=>$manual->id, 'part_number'=>'ATTACH-12R']], ['component'=>(object)['manual_id'=>$manual->id, 'part_number'=>'OTHER']],
        ]));
    }
}
