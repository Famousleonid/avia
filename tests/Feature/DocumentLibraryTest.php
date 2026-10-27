<?php

namespace Tests\Feature;

use App\Models\DocumentCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\BuildsDomainData;
use Tests\TestCase;

class DocumentLibraryTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_video_upload_is_restricted_to_video_category_and_can_be_viewed_and_downloaded(): void
    {
        $wo = $this->createWorkorder();
        $this->actingAs($this->createUserWithRole('Admin'));
        $url = route('workorders.pdf.store', $wo);
        $mp4 = pack('N', 24).'ftypmp42'.pack('N', 0).'mp42isom';
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('inspection.mp4', $mp4, 'video/mp4'), 'doc_kind' => 'general'])->assertUnprocessable();
        $this->postJson($url, ['pdf' => $this->makeUploadedImage('photo.png'), 'doc_kind' => 'video'])->assertUnprocessable();
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('fake.mp4', 'not a video', 'video/mp4'), 'doc_kind' => 'video'])->assertUnprocessable();
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('inspection.mp4', $mp4, 'video/mp4'), 'doc_kind' => 'video'])->assertOk();
        $media = $wo->fresh()->getMedia('pdfs')->first();
        $this->assertSame('video', $media->getCustomProperty('doc_kind'));
        $this->assertSame('video/mp4', $media->mime_type);
        $this->get(route('workorders.pdf.show', [$wo->id, $media->id]))->assertOk()->assertHeader('Content-Type', 'video/mp4');
        $this->get(route('workorders.pdf.download', [$wo->id, $media->id]))->assertOk();
        $list = $this->getJson(route('workorders.pdfs', $wo))->assertOk();
        $list->assertJsonPath('pdfs.0.kind', 'video')->assertJsonPath('pdfs.0.kind_label', 'Video');
        $this->assertContains('video', array_column($list->json('upload_categories'), 'key'));
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('huge.mp4', $mp4.str_repeat(' ', 51201 * 1024), 'video/mp4'), 'doc_kind' => 'video'])->assertUnprocessable();
    }

    public function test_only_system_admin_can_manage_categories(): void
    {
        $category = DocumentCategory::create(['name' => 'Scans access test']);
        foreach ([['Admin', false], ['Manager', false], ['Technician', false]] as [$role, $flag]) {
            $this->flushSession();
            $user = $this->createUserWithRole($role, ['is_admin' => $flag]);
            $this->actingAs($user)->get(route('document_categories.index'))->assertForbidden();
            $this->post(route('document_categories.store'), ['name' => 'Forbidden'])->assertForbidden();
            $this->delete(route('document_categories.destroy', $category))->assertForbidden();
            $this->patchJson(route('directories.field.update', ['directory' => 'document_categories', 'id' => $category->id, 'field' => 'name']), ['name' => 'Forbidden'])->assertForbidden();
        }
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $this->flushSession();
        $this->actingAs($admin)->get(route('document_categories.index'))->assertOk()->assertSee('Document Categories');
        $this->post(route('document_categories.store'), ['name' => 'Scan category'])->assertRedirect();
        $created = DocumentCategory::where('name', 'Scan category')->firstOrFail();
        $this->assertStringStartsWith('category_', $created->key);
        $this->delete(route('document_categories.destroy', $created))->assertRedirect();
        $this->assertSoftDeleted('document_categories', ['id' => $created->id]);
        $this->deleteJson(route('document_categories.destroy', DocumentCategory::where('key', 'general')->firstOrFail()))->assertUnprocessable();
    }

    public function test_embedded_categories_keep_dialog_context_after_changes(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $this->actingAs($admin);
        $index = route('document_categories.index', ['embedded' => 1]);
        $this->get($index)->assertOk()->assertSee('name="embedded" value="1"', false);
        $this->post(route('document_categories.store'), ['name' => 'Dialog category', 'embedded' => 1])->assertRedirect($index);
        $category = DocumentCategory::where('name', 'Dialog category')->firstOrFail();
        $this->put(route('document_categories.update', $category), ['name' => 'Dialog renamed', 'embedded' => 1])->assertRedirect($index);
        $this->assertSame('Dialog renamed', $category->fresh()->name);
        $this->delete(route('document_categories.destroy', $category), ['embedded' => 1])->assertRedirect($index);
        $this->assertSoftDeleted('document_categories', ['id' => $category->id]);
        $this->flushSession();
        $this->actingAs($this->createUserWithRole('Admin', ['is_admin' => false]))->get($index)->assertForbidden();
    }

    public function test_scan_upload_view_download_and_category_removal_preserve_file(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder();
        $category = DocumentCategory::create(['name' => 'Inspection scans']);
        $this->actingAs($admin)->postJson(route('workorders.pdf.store', $wo), [
            'pdf' => $this->makeUploadedImage('scan.png'), 'doc_kind' => $category->key,
        ])->assertOk();
        $media = $wo->fresh()->getMedia('pdfs')->first();
        $this->assertSame('image/png', $media->mime_type);
        $this->assertStringEndsWith('.png', $media->file_name);
        $this->get(route('workorders.pdf.show', [$wo->id, $media->id]))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('workorders.pdf.download', [$wo->id, $media->id]))->assertOk();
        $category->delete();
        $list = $this->getJson(route('workorders.pdfs', $wo))->assertOk();
        $this->assertSame('Inspection scans', $list->json('pdfs.0.kind_label'));
        $this->assertNotContains($category->key, array_column($list->json('upload_categories'), 'key'));
        $this->assertContains($category->key, array_column($list->json('document_categories'), 'key'));
        $this->get(route('workorders.pdf.show', [$wo->id, $media->id]))->assertOk();
        $this->postJson(route('workorders.pdf.store', $wo), [
            'pdf' => $this->makeUploadedImage('scan.png'), 'doc_kind' => $category->key,
        ])->assertUnprocessable();
    }

    public function test_document_edit_updates_name_and_category_without_changing_file_or_source(): void
    {
        $wo = $this->createWorkorder();
        $this->actingAs($this->createUserWithRole('Technician'));
        $category = DocumentCategory::create(['name' => 'Edited category']);
        $this->postJson(route('workorders.pdf.store', $wo), ['pdf' => $this->makeUploadedImage('edit.png')])->assertOk();
        $media = $wo->fresh()->getMedia('pdfs')->first();
        $originalFile = $media->file_name;
        $originalHash = hash_file('sha256', $media->getPath());
        $url = route('workorders.pdf.rename', $media->id);
        foreach (['bad-category', 'video', 'machining'] as $invalid) {
            $this->patchJson($url, ['name' => 'Invalid change', 'doc_kind' => $invalid])->assertUnprocessable();
            $this->assertNotSame('Invalid change', $media->fresh()->getCustomProperty('document_name'));
        }
        $this->patchJson($url, ['name' => 'Inspection report', 'doc_kind' => $category->key])->assertOk();
        $this->getJson(route('workorders.pdfs', $wo))->assertOk()
            ->assertJsonPath('pdfs.0.name', 'Inspection report')->assertJsonPath('pdfs.0.kind', $category->key)
            ->assertJsonPath('pdfs.0.kind_label', 'Edited category');
        $category->delete();
        $this->patchJson($url, ['name' => 'Retain removed category', 'doc_kind' => $category->key])->assertOk();
        $this->patchJson($url, ['name' => 'Back to general', 'doc_kind' => 'general'])->assertOk();
        $this->patchJson($url, ['name' => 'Cannot reassign removed', 'doc_kind' => $category->key])->assertUnprocessable();
        // Existing rename-only clients retain the category.
        $this->patchJson($url, ['name' => 'Name only'])->assertOk();
        $this->assertSame('general', $media->fresh()->getCustomProperty('doc_kind'));

        $media = $media->fresh();
        $media->forgetCustomProperty('doc_kind');
        $media->setCustomProperty('source', 'machining_final');
        $media->setCustomProperty('document_id', 123);
        $media->save();
        $this->patchJson($url, ['name' => 'Machining report', 'doc_kind' => 'general'])->assertOk();
        $this->getJson(route('workorders.pdfs', $wo))->assertOk()->assertJsonPath('pdfs.0.kind', 'general')
            ->assertJsonPath('pdfs.0.is_generated', true)->assertJsonPath('pdfs.0.source_kind', 'machining');
        $this->patchJson($url, ['name' => 'Machining report', 'doc_kind' => 'machining'])->assertOk();
        $this->getJson(route('workorders.pdfs', $wo))->assertJsonPath('pdfs.0.kind', 'machining');
        $this->assertSame('machining_final', $media->fresh()->getCustomProperty('source'));
        $this->assertSame(123, $media->fresh()->getCustomProperty('document_id'));
        $this->assertSame($originalFile, $media->fresh()->file_name);
        $this->assertSame($originalHash, hash_file('sha256', $media->fresh()->getPath()));
    }

    public function test_pdf_still_uploads_and_unsupported_or_large_files_are_rejected(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder();
        $url = route('workorders.pdf.store', $wo);
        $this->actingAs($admin)->postJson($url, ['pdf' => $this->makeUploadedFile('report.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF", 'application/pdf')])->assertOk();
        $media = $wo->fresh()->getMedia('pdfs')->first();
        $this->get(route('workorders.pdf.show', [$wo->id, $media->id]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        foreach ([26, 50] as $megabytes) {
            $header = "%PDF-1.4\n";
            $this->postJson($url, ['pdf' => $this->makeUploadedFile('large-report.pdf', $header.str_repeat(' ', $megabytes * 1024 * 1024 - strlen($header)), 'application/pdf')])->assertOk();
        }
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('report.pdf', "%PDF-1.4\n".str_repeat(' ', 51201 * 1024), 'application/pdf')])->assertUnprocessable();
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('script.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/svg+xml')])->assertUnprocessable();
        $this->postJson($url, ['pdf' => $this->makeUploadedImage('scan.png'), 'doc_kind' => 'fc'])->assertUnprocessable();
    }
}
