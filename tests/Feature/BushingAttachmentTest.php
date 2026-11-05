<?php
namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\{BuildsDomainData, TestCase};

class BushingAttachmentTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_oversize_variants_share_one_document_and_replacement_removes_legacy_variant_file(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $manual = $this->createManual();
        $original = Component::create(['manual_id' => $manual->id, 'name' => 'BUSHING', 'part_number' => '2225-0702', 'ipl_num' => '6-203', 'bush_ipl_num' => '6-203', 'is_bush' => true]);
        $variant = Component::create(['manual_id' => $manual->id, 'name' => 'BUSHING', 'part_number' => '2225-0702FS01', 'ipl_num' => '6-204', 'bush_ipl_num' => '6-203', 'is_bush' => true]);
        $legacy = $variant->addMedia($this->makeUploadedImage('legacy.png'))->toMediaCollection('bushing_documents', 'private');
        $response = $this->postJson(route('components.bushing-documents.store', $variant), ['file' => $this->makeUploadedImage('shared.png')])->assertCreated();
        $media = Media::findOrFail($response->json('id'));
        try {
            $this->assertSame((int) $original->id, (int) $media->model_id);
            $this->assertNull(Media::find($legacy->id));
            $this->get($response->json('url'))->assertOk();
            $family = new \App\Services\BushingDocumentFamily();
            $this->assertSame($media->id, $family->document($original)->id);
            $this->assertSame($media->id, $family->document($variant)->id);
            $this->assertSame($original->id, $family->owner($variant)->id);
            $audit = app(\App\Services\Ai\Tools\SearchActivityLogsTool::class)->run($this->createUserWithRole('Admin'), [
                'area' => 'bushing_documents', 'part_number' => '2225-0702FS01',
            ]);
            $this->assertSame(1, $audit['count']);
            $this->assertSame('shared.png', $audit['matches'][0]['attachment']['file']);
        } finally {
            $media->delete();
        }
    }

    public function test_attachment_belongs_to_part_and_cannot_be_read_as_another_part(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $part = Component::create(['manual_id' => $this->createManual()->id, 'name' => 'BUSHING', 'part_number' => 'B-1', 'ipl_num' => '1-1', 'is_bush' => true]);
        $other = Component::create(['manual_id' => $part->manual_id, 'name' => 'BUSHING', 'part_number' => 'B-2', 'ipl_num' => '1-2', 'is_bush' => true]);
        $response = $this->postJson(route('components.bushing-documents.store', $part), ['file' => $this->makeUploadedImage('bushing.png')])->assertCreated();
        $media = Media::findOrFail($response->json('id'));
        try {
            $this->assertSame((int) $part->id, (int) $media->model_id);
            $this->assertSame('bushing_documents', $media->collection_name);
            $this->assertSame('private', $media->disk);
            $this->get($response->json('url'))->assertOk();
            $this->get(route('components.bushing-documents.show', [$other, $media]))->assertNotFound();
            $this->postJson(route('components.bushing-documents.store', $part), ['file' => $this->makeUploadedFile('bad.txt', 'not an image', 'text/plain')])->assertUnprocessable();
            $this->assertFileExists($media->getPath());
            $oldPath = $media->getPath();
            $replacement = $this->postJson(route('components.bushing-documents.store', $part), ['file' => $this->makeUploadedImage('replacement.png')])->assertCreated();
            $this->assertNull(Media::find($media->id));
            $this->assertFileDoesNotExist($oldPath);
            $this->assertSame(1, $part->media()->where('collection_name', 'bushing_documents')->count());
            $log = \Spatie\Activitylog\Models\Activity::where('description', 'Bushing document replaced')->where('subject_id', $part->id)->latest('id')->firstOrFail();
            $this->assertSame((int) auth()->id(), (int) $log->causer_id);
            $this->assertSame('bushing.png', $log->properties['old'][0]['file']);
            $this->assertSame('replacement.png', $log->properties['attributes']['file']);
            $media = Media::findOrFail($replacement->json('id'));
            $this->get($replacement->json('url'))->assertOk();
        } finally {
            $media->delete();
        }
        $pdf = $this->postJson(route('components.bushing-documents.store', $part), ['file' => $this->makeUploadedFile('drawing.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF", 'application/pdf')])->assertCreated();
        Media::findOrFail($pdf->json('id'))->delete();
    }
}
