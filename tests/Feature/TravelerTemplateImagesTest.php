<?php

namespace Tests\Feature;

use App\Models\{Component, ManualProcess, Process, ProcessName, Tdr, TdrProcess, TravelerProcessTemplate, TravelerTemplateImage};
use App\Services\TravelerTemplateImages;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\{BuildsDomainData, TestCase};

class TravelerTemplateImagesTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        Storage::fake('local');
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $manual = $wo->unit->manuals;
        $user->permittedManuals()->attach($manual);
        $part = Component::create(['manual_id' => $manual->id, 'part_number' => 'IMAGE-1', 'ipl_num' => '1-10', 'name' => 'Pin']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id]);
        $name = ProcessName::firstOrCreate(['name' => 'NDT-4']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'Image test spec']);
        ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $process->id]);
        $template = TravelerProcessTemplate::create(['manual_id' => $manual->id, 'part_number' => $part->part_number, 'name' => 'Images', 'active' => true, 'revision' => 1]);
        $template->steps()->create(['process_id' => $process->id, 'sort_order' => 1, 'traveler_group' => 1]);
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        $url = route('manuals.traveler-templates.images.store', [$manual, $template]);
        return compact('user', 'wo', 'manual', 'tdr', 'template', 'url');
    }

    public function test_images_are_live_ordered_private_and_printed_after_traveler(): void
    {
        $f = $this->fixture();
        // Apply first, then upload: pre-existing Travelers must receive the new images.
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $f['template']->id, 'revision' => 1])->assertOk();
        $result = $this->post($f['url'], ['revision' => 1, 'images' => [$this->makeUploadedImage('first.png'), $this->makeUploadedImage('second.png')]], ['Accept' => 'application/json'])->assertOk()->json('template');
        $this->assertCount(2, $result['images']);
        $this->assertSame(2, $result['revision']);
        $this->assertArrayNotHasKey('path', $result['images'][0]);
        $images = TravelerTemplateImages::forTdr($f['tdr']);
        $this->assertSame(['first.png', 'second.png'], $images->pluck('name')->all());
        Storage::disk('local')->assertExists($images[0]->path);
        $this->get($images[0]->url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $f['user']->permittedManuals()->detach($f['manual']);
        $this->get($images[0]->url)->assertForbidden();
        $this->get(route('tdr.traveler-template-images.show', [$f['tdr'], $images[0]]))->assertOk();
        $f['user']->permittedManuals()->attach($f['manual']);
        $change = route('manuals.traveler-templates.images.change', [$f['manual'], $f['template'], $images[1]]);
        $this->postJson($change, ['revision' => 2, 'action' => 'up'])->assertOk()->assertJsonPath('template.images.0.name', 'second.png');
        $this->assertSame(['second.png', 'first.png'], TravelerTemplateImages::forTdr($f['tdr'])->pluck('name')->all());
        $response = $this->get(route('tdr-processes.travelForm', ['id' => $f['tdr']->id, 'traveler_group' => 1]))->assertOk();
        $response->assertSee('data-traveler-print-image', false)->assertSee('page: traveler-image', false);
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'alt="first.png"') + substr_count($html, 'alt="second.png"'));
        $this->assertGreaterThan(strpos($html, '</table>'), strpos($html, 'class="traveler-image-attachments"'));
        $this->get(route('tdr-processes.travelForm', ['id' => $f['tdr']->id, 'traveler_group' => 99]))->assertOk()->assertDontSee('class="traveler-image-attachments"', false);
        $this->postJson($change, ['revision' => 3, 'action' => 'delete'])->assertOk();
        $this->assertSame(['first.png'], TravelerTemplateImages::forTdr($f['tdr'])->pluck('name')->all());
        $this->get(route('tdr.traveler-template-images.show', [$f['tdr'], $images[1]]))->assertNotFound();
        $this->assertNotNull(TravelerTemplateImage::withTrashed()->find($images[1]->id)->deleted_at);
        TdrProcess::where('tdrs_id', $f['tdr']->id)->delete();
        $this->assertCount(0, TravelerTemplateImages::forTdr($f['tdr']));
    }

    public function test_pdf_is_uploaded_served_privately_and_included_in_traveler(): void
    {
        $f = $this->fixture();
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $f['template']->id, 'revision' => 1])->assertOk();
        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml('<h1>First PDF page</h1><div style="page-break-before:always">Second PDF page</div>');
        $pdf->render();
        $result = $this->post($f['url'], ['revision' => 1, 'images' => [
            $this->makeUploadedFile('drawing.pdf', $pdf->output(), 'application/pdf'),
        ]], ['Accept' => 'application/json'])->assertOk()->json('template');
        $this->assertSame('application/pdf', $result['images'][0]['mime']);
        $image = TravelerTemplateImages::forTdr($f['tdr'])->sole();
        $this->get($image->url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tdr.traveler-template-images.show', [$f['tdr'], $image]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tdr-processes.travelForm', ['id' => $f['tdr']->id, 'traveler_group' => 1]))
            ->assertOk()->assertSee('data-traveler-pdf=', false)->assertSee('drawing.pdf')
            ->assertSee('travelerAttachmentsReady');
        $this->post($f['url'], ['revision' => 2, 'images' => [
            $this->makeUploadedFile('fake.pdf', '<script>bad</script>', 'application/pdf'),
        ]], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_form_attachment_icon_and_viewer_are_scoped_to_the_selected_form(): void
    {
        $f = $this->fixture();
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $f['template']->id, 'revision' => 1])->assertOk();
        $row = TdrProcess::where('tdrs_id', $f['tdr']->id)->firstOrFail();
        $vars = ['tdrProcessRow' => $row, 'current_tdr' => $f['tdr'], 'tdrProcesses' => collect([$row]),
            'attachmentGroup' => 1, 'docsByRp' => [], 'phaseDocsByRp' => []];
        $this->assertStringContainsString('No form attachments', view('admin.tdr-processes.partials.processes-body-document-controls', $vars)->render());
        $this->post($f['url'], ['revision' => 1, 'images' => [$this->makeUploadedImage('form-picture.png')]], ['Accept' => 'application/json'])->assertOk();
        $html = view('admin.tdr-processes.partials.processes-body-document-controls', $vars)->render();
        $this->assertStringContainsString('View form attachments', $html);
        $this->assertStringContainsString('bi-image', $html);
        $this->get(route('tdr-processes.attachments', ['tdr' => $f['tdr'], 'traveler_group' => 1]))->assertOk()->assertSee('form-picture.png');
        $this->get(route('tdr-processes.attachments', ['tdr' => $f['tdr'], 'row' => $row->id]))->assertOk()->assertDontSee('form-picture.png');
        $this->get(route('tdr-processes.attachments', ['tdr' => $f['tdr'], 'traveler_group' => 99]))->assertNotFound();
        $other = Tdr::create(['workorder_id' => $f['wo']->id, 'component_id' => $f['tdr']->component_id]);
        $this->get(route('tdr-processes.attachments', ['tdr' => $other, 'row' => $row->id]))->assertNotFound();
        $vars['attachmentGroup'] = null;
        $this->assertStringContainsString('No form attachments', view('admin.tdr-processes.partials.processes-body-document-controls', $vars)->render());
        $vars['tdrProcessRow']->rule_process_ids = [123];
        $vars['docsByRp'] = [123 => [(object) ['id' => 456]]];
        $this->assertStringContainsString('View form attachments', view('admin.tdr-processes.partials.processes-body-document-controls', $vars)->render());
    }

    public function test_template_preview_uses_traveler_form_without_creating_workorder_data(): void
    {
        $f = $this->fixture();
        $f['template']->steps()->update(['description' => 'Preview extra instruction']);
        $before = [TdrProcess::count(), Tdr::count(), \App\Models\Workorder::count()];
        $url = route('manuals.traveler-templates.preview', [$f['manual'], $f['template']]);
        $this->get($url)->assertOk()->assertSee('PART TRAVELER')->assertDontSee('Template preview:')
            ->assertSee('Image test spec')->assertSee('Preview extra instruction')->assertSee('IMAGE-1');
        $this->assertSame($before, [TdrProcess::count(), Tdr::count(), \App\Models\Workorder::count()]);
        $other = $this->createManual();
        $f['user']->permittedManuals()->attach($other);
        $this->get(route('manuals.traveler-templates.preview', [$other, $f['template']]))->assertNotFound();
        $f['user']->permittedManuals()->detach();
        $this->get($url)->assertForbidden();
    }

    public function test_preview_does_not_insert_outgoing_inspection_next_to_an_explicit_step(): void
    {
        $f = $this->fixture();
        $url = route('manuals.traveler-templates.preview', [$f['manual'], $f['template']]);
        $this->assertSame(1, substr_count($this->get($url)->assertOk()->getContent(), '<strong>Outcoming inspection</strong>'));
        foreach (['INSPECT', 'Outcoming Inspection', 'Outgoing inspection'] as $label) {
            $name = ProcessName::firstOrCreate(['name' => $label], ['process_sheet_name' => 'Inspection']);
            $process = Process::create(['process_names_id' => $name->id, 'process' => 'Visual']);
            $step = $f['template']->steps()->create([
                'process_id' => $process->id, 'sort_order' => 0, 'traveler_group' => null,
                'description' => 'Inspection stage: outgoing',
            ]);
            foreach ([null, 1] as $group) {
                $step->update(['traveler_group' => $group]);
                $html = $this->get($url)->assertOk()->assertSee('Inspection stage: outgoing')->getContent();
                $this->assertSame(0, substr_count($html, '<strong>Outcoming inspection</strong>'), $label);
            }
            $step->delete();
        }
        $name = ProcessName::firstOrCreate(['name' => 'INSPECT'], ['process_sheet_name' => 'Inspection']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'Visual']);
        $f['template']->steps()->create([
            'process_id' => $process->id, 'sort_order' => 0, 'traveler_group' => null,
            'description' => 'Inspection stage: receiving',
        ]);
        $this->assertSame(1, substr_count($this->get($url)->assertOk()->getContent(), '<strong>Outcoming inspection</strong>'));
    }

    public function test_upload_rejects_invalid_files_stale_revision_and_foreign_manual(): void
    {
        $f = $this->fixture();
        $this->post($f['url'], ['revision' => 1, 'images' => [$this->makeUploadedFile('bad.svg', '<svg/>', 'image/svg+xml')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post($f['url'], ['revision' => 2, 'images' => [$this->makeUploadedImage('stale.png')]], ['Accept' => 'application/json'])->assertStatus(409);
        $this->assertCount(0, $f['template']->images);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $other = $this->createManual();
        $f['user']->permittedManuals()->attach($other);
        $this->post(route('manuals.traveler-templates.images.store', [$other, $f['template']]), ['revision' => 1, 'images' => [$this->makeUploadedImage('wrong.png')]], ['Accept' => 'application/json'])->assertNotFound();
        $f['user']->permittedManuals()->detach();
        $this->post($f['url'], ['revision' => 1, 'images' => [$this->makeUploadedImage('denied.png')]], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_other_tdr_cannot_read_unrelated_image_and_metadata_preserves_images(): void
    {
        $f = $this->fixture();
        $image = $this->post($f['url'], ['revision' => 1, 'images' => [$this->makeUploadedImage('one.png')]], ['Accept' => 'application/json'])->assertOk()->json('template.images.0');
        $otherTdr = Tdr::create(['workorder_id' => $f['wo']->id, 'component_id' => $f['tdr']->component_id]);
        $this->get(route('tdr.traveler-template-images.show', [$otherTdr, $image['id']]))->assertNotFound();
        $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $f['template']]), ['revision' => 2, 'part_number' => 'IMAGE-1', 'name' => 'Renamed', 'active' => true])
            ->assertOk()->assertJsonPath('template.images.0.id', $image['id']);
        $this->postJson(route('manuals.traveler-templates.step', [$f['manual'], $f['template']]), ['revision' => 3, 'action' => 'save', 'id' => $f['template']->steps[0]->id, 'process_id' => $f['template']->steps[0]->process_id, 'description' => 'Note'])
            ->assertOk()->assertJsonPath('template.images.0.id', $image['id']);
    }
}
