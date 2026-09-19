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

    public function test_pdf_still_uploads_and_unsupported_or_large_files_are_rejected(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder();
        $url = route('workorders.pdf.store', $wo);
        $this->actingAs($admin)->postJson($url, ['pdf' => $this->makeUploadedFile('report.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF", 'application/pdf')])->assertOk();
        $media = $wo->fresh()->getMedia('pdfs')->first();
        $this->get(route('workorders.pdf.show', [$wo->id, $media->id]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('report.pdf', "%PDF-1.4\n".str_repeat(' ', 10241 * 1024), 'application/pdf')])->assertUnprocessable();
        $this->postJson($url, ['pdf' => $this->makeUploadedFile('script.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/svg+xml')])->assertUnprocessable();
        $this->postJson($url, ['pdf' => $this->makeUploadedImage('scan.png'), 'doc_kind' => 'fc'])->assertUnprocessable();
    }
}
