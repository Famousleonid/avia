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
        $url = route('process-print-attachments.store', $process);
        $this->get(route('process-print-attachments.index', $process))->assertOk();
        $this->postJson($url, ['part_number'=>'  abc-12R ', 'image'=>$this->image('drawing.png')])->assertOk();
        $first = $process->fresh()->printAttachmentFor('ABC-12r');
        $this->assertNotNull($first);
        $this->get(route('process-print-attachments.image', [$process, $first]))->assertOk();
        $this->get(route('process-print-attachments.image', [$other, $first]))->assertNotFound();
        $this->deleteJson(route('process-print-attachments.destroy', [$other, $first]))->assertNotFound();
        $this->postJson($url, ['part_number'=>'ABC-12R', 'image'=>$this->invalidFile()])->assertUnprocessable();
        $this->assertNotNull($process->fresh()->printAttachmentFor('ABC-12R'));
        $this->postJson($url, ['part_number'=>'ABC-12R', 'image'=>$this->image('replacement.png')])->assertOk();
        $second = $process->fresh()->printAttachmentFor('ABC-12R');
        $this->assertNotEquals($first->id, $second->id);
        $this->assertCount(1, $process->fresh()->getMedia(ProcessName::PRINT_ATTACHMENT_COLLECTION));
        $this->assertFalse(is_file($first->getPath()));
        $this->assertNull($process->fresh()->printAttachmentFor('ABC-12'));
        $tech = $this->createUserWithRole('Technician');
        $this->actingAs($tech)->withSession(['password_hash_web'=>$tech->getAuthPassword()]);
        $this->get(route('process-print-attachments.index', $process))->assertForbidden();
        $this->postJson($url, [])->assertForbidden();
        $this->deleteJson(route('process-print-attachments.destroy', [$process, $second]))->assertForbidden();
        $this->get(route('process-print-attachments.image', [$process, $second]))->assertOk();
        $this->actingAs($manager)->withSession(['password_hash_web'=>$manager->getAuthPassword()]);
        $this->deleteJson(route('process-print-attachments.destroy', [$process, $second]))->assertOk();
        $this->assertNull($process->fresh()->printAttachmentFor('ABC-12R'));
        $logs = Activity::where('subject_type', ProcessName::class)->where('subject_id', $process->id)
            ->whereIn('description', ['Print attachment added', 'Print attachment replaced', 'Print attachment deleted'])->get();
        $this->assertCount(3, $logs);
        $this->assertTrue($logs->every(fn ($log) => $log->causer_id === $manager->id));
    }

    public function test_single_part_form_appends_only_matching_image_and_preserves_suffix(): void
    {
        Storage::fake('private');
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder();
        $component = $this->createComponent($wo->unit->manual);
        $component->update(['part_number'=>'ATTACH-12R']);
        $tdr = Tdr::create(['workorder_id'=>$wo->id, 'component_id'=>$component->id, 'qty'=>1]);
        $name = ProcessName::create(['name'=>'US Measurement', 'process_sheet_name'=>'NDT', 'form_number'=>'016', 'print_form'=>true]);
        $row = TdrProcess::create(['tdrs_id'=>$tdr->id, 'process_names_id'=>$name->id, 'processes'=>[]]);
        $this->postJson(route('process-print-attachments.store', $name), [
            'part_number'=>'attach-12r', 'image'=>$this->image('diagram.png'),
        ])->assertOk();
        $media = $name->fresh()->printAttachmentFor('ATTACH-12R');
        $url = route('tdr-processes.show', $row);
        $html = $this->get($url)->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<section class="process-part-attachment"'));
        $this->assertStringContainsString('Part No: ATTACH-12R', $html);
        $this->assertStringContainsString('W'.$wo->number, $html);
        $this->assertStringContainsString('system-print-qr__code', $html);
        $html = str_replace(route('process-print-attachments.image', [$name, $media]), 'data:image/png;base64,'.base64_encode(file_get_contents($media->getPath())), $html);
        $html = str_replace('<link rel="stylesheet" href="'.asset('assets/Bootstrap 5/bootstrap.min.css').'">', '<style>'.file_get_contents(public_path('assets/Bootstrap 5/bootstrap.min.css')).'</style>', $html);
        file_put_contents(base_path('codex-test-runtime/process-attachment-form.html'), $html);
        $component->update(['part_number'=>'ATTACH-12']);
        $this->assertStringNotContainsString('<section class="process-part-attachment"', $this->get($url)->assertOk()->getContent());
        $this->assertNull(app(ProcessPrintAttachment::class)->forForm($name->fresh(), [], [
            ['component'=>(object)['part_number'=>'ATTACH-12R']], ['component'=>(object)['part_number'=>'OTHER']],
        ]));
    }
}
