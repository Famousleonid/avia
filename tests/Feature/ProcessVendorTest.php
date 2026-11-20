<?php

namespace Tests\Feature;

use App\Models\{Component, GeneralTask, Main, Process, ProcessName, ProcessVendorCheck, ProcessVendorRepairOrder, StdProcess, Task, Tdr, TdrProcess, Unit, Vendor, WoBushing, WoBushingLine, WoBushingProcess, WorkorderStdProcess};
use App\Services\ProcessVendorQueue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class ProcessVendorTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    private function part($wo, string $name = 'Chrome strip', int $sort = 1, ?Tdr $tdr = null): TdrProcess
    {
        $component = $tdr?->component ?? Component::firstOrCreate(['manual_id' => $wo->unit->manual_id, 'part_number' => 'PV-PART-'.$wo->id], ['name' => 'Main fitting', 'ipl_num' => '1-10', 'units_assy' => 2]);
        $tdr ??= Tdr::create(['workorder_id' => $wo->id, 'component_id' => $component->id, 'qty' => 2, 'serial_number' => 'PV-SERIAL', 'tdr_type' => Tdr::TYPE_COMPONENT_TDR]);
        $pn = ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => strtoupper($name), 'form_number' => 'PV', 'show_in_process_picker' => true]);
        return TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $pn->id, 'sort_order' => $sort]);
    }

    private function rows($wo) { return app(ProcessVendorQueue::class)->rows([$wo->id]); }

    public function test_shimmy_releases_any_part_before_approval_and_inspection_through_cache_shipping_and_ro(): void
    {
        config(['cache.default' => 'array']);
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder();
        $cache = app(\App\Services\ProcessVendorCache::class);
        $cache->refresh();
        $part = $this->part($wo, 'Shimmy Damper O/H');
        // Different P/N from WO108173: eligibility belongs to the process, not a part number.
        $this->assertNotSame('2265A0000-03', $part->tdr->component->part_number);
        $this->assertNull($wo->approve_at);
        $this->assertCount(0, $wo->main);
        $this->postJson(route('process-vendor.refresh'))->assertOk()->assertJsonCount(1, 'search_rows');
        $row = $cache->read()['rows']->sole();
        $this->assertSame('Shimmy Damper O/H', $row['process_name']);
        $items = $this->items(collect([$row]));
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertOk();
        $vendor = Vendor::create(['name' => 'Early shimmy vendor']);
        $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id])->assertOk();
        $order = ProcessVendorRepairOrder::latest('id')->firstOrFail();
        $this->assertCount(0, $cache->read()['rows']);
        $this->assertNull($part->fresh()->date_start);
        $this->assertNull($wo->fresh()->approve_at);
        $this->postJson(route('process-vendor.orders.cancel', $order))->assertOk();
        $this->assertTrue($cache->read()['rows']->sole()['checked']);
        \Illuminate\Support\Facades\DB::table('workorders')->where('id', $wo->id)->update(['approve_at' => now()]);
        $this->assertSame($row['fingerprint'], $this->rows($wo)->sole()['fingerprint']);
    }

    public function test_shimmy_exception_preserves_sequence_and_does_not_release_other_processes(): void
    {
        $wo = $this->createWorkorder();
        $first = $this->part($wo, 'Machining');
        $shimmy = $this->part($wo, 'Shimmy Damper O/H', 2, $first->tdr);
        $next = $this->part($wo, 'Nickel', 3, $first->tdr);
        $this->assertCount(0, $this->rows($wo));
        $first->update(['date_start' => today(), 'date_finish' => today()]);
        $this->assertSame('Shimmy Damper O/H', $this->rows($wo)->sole()['process_name']);
        foreach ([['ignore_row' => true], ['repair_order' => 'ALREADY-ASSIGNED'], ['date_start' => today()]] as $change) {
            $shimmy->update($change);
            $this->assertCount(0, $this->rows($wo));
            $shimmy->update(['ignore_row' => false, 'repair_order' => null, 'date_start' => null]);
        }
        $shimmy->tdr->update(['qty' => 0]); $this->assertCount(0, $this->rows($wo));
        $shimmy->tdr->update(['qty' => 2, 'result_status' => 'scrapped']); $this->assertCount(0, $this->rows($wo));
        $shimmy->tdr->update(['result_status' => null]);
        $shimmy->update(['date_start' => today(), 'date_finish' => today()]);
        $this->assertCount(0, $this->rows($wo)); // Nickel still requires approval.
        $shimmy->update(['date_start' => null, 'date_finish' => null]);
        $wo->forceFill(['done_at' => today()])->save();
        $this->assertCount(0, $this->rows($wo));
    }

    public function test_shimmy_exception_does_not_release_mixed_traveler_before_approval(): void
    {
        $wo = $this->createWorkorder();
        $shimmy = $this->part($wo, 'Shimmy Damper O/H');
        $shimmy->update(['in_traveler' => true, 'traveler_group' => 1]);
        $this->assertSame('Traveler', $this->rows($wo)->sole()['process_name']);
        $other = $this->part($wo, 'Nickel', 2, $shimmy->tdr);
        $other->update(['in_traveler' => true, 'traveler_group' => 1]);
        $this->assertCount(0, $this->rows($wo));
    }

    public function test_ro_history_searches_all_pages_and_preserves_snapshot_identity(): void
    {
        $url = route('process-vendor.orders.index');
        $this->getJson($url)->assertUnauthorized();
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $vendor = Vendor::create(['name' => 'Renamed current vendor']);
        for ($i = 1; $i <= 25; $i++) {
            ProcessVendorRepairOrder::create(['number' => 'HISTORY-QA-'.str_pad($i, 3, '0', STR_PAD_LEFT),
                'vendor_id' => $vendor->id, 'created_by' => $user->id,
                'document' => ['vendor_name' => 'Original 100%_vendor', 'items' => [['wo' => 'HIST-WO-'.$i]]],
                'cancelled_at' => $i <= 5 ? now() : null]);
        }
        $this->getJson($url.'?q=HISTORY-QA')->assertOk()->assertJsonPath('total', 25)
            ->assertJsonCount(20, 'orders')->assertJsonPath('last_page', 2)->assertJsonPath('orders.0.number', 'HISTORY-QA-025');
        $this->getJson($url.'?q=HISTORY-QA&page=2')->assertOk()->assertJsonCount(5, 'orders')
            ->assertJsonPath('orders.4.number', 'HISTORY-QA-001');
        $this->getJson($url.'?q=HIST-WO-25')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('orders.0.vendor', 'Original 100%_vendor')->assertJsonPath('orders.0.workorders.0', 'HIST-WO-25');
        $this->getJson($url.'?q='.urlencode('100%_vendor').'&status=cancelled')->assertOk()->assertJsonPath('total', 5)
            ->assertJsonPath('orders.0.status', 'Cancelled');
        $this->getJson($url.'?q=Renamed%20current%20vendor')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($url.'?q=HISTORY-QA&status=prepared')->assertOk()->assertJsonPath('total', 20);
        $this->getJson($url.'?status=sent')->assertUnprocessable();
    }
    private function items($rows): array { return $rows->map(fn ($row) => array_intersect_key($row, array_flip(['key', 'workorder_id', 'fingerprint'])))->all(); }

    private function inspection($wo): Main
    {
        $gt = GeneralTask::create(['name' => 'PV QA']);
        $task = Task::create(['name' => 'Post Disassembly inspection', 'general_task_id' => $gt->id, 'task_has_start_date' => false]);
        return Main::create(['workorder_id' => $wo->id, 'general_task_id' => $gt->id, 'task_id' => $task->id, 'date_finish' => today(), 'user_id' => $wo->user_id]);
    }

    private function std($wo, string $type, Component $component): WorkorderStdProcess
    {
        $name = \App\Services\WorkorderStdListProcessesService::NAME_BY_KEY[$type];
        $pn = ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => strtoupper($type), 'form_number' => 'PV']);
        StdProcess::updateOrCreate(['manual_id' => $component->manual_id, 'component_id' => $component->id, 'std' => $type], ['qty' => 2, 'process' => 'TEST SPEC']);
        return WorkorderStdProcess::create(['workorder_id' => $wo->id, 'std_type' => $type, 'process_name_id' => $pn->id]);
    }

    public function test_std_ndt_needs_inspection_and_other_std_needs_approval_and_previous_return(): void
    {
        $wo = $this->createWorkorder(['instruction_id' => $this->createOverhaulInstruction()->id, 'scope_type' => Unit::SCOPE_FULL_UNIT]);
        $c = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'STD-PIN', 'name' => 'Pin', 'ipl_num' => '1-10', 'units_assy' => 2, 'ndt_list' => true, 'cad_list' => true]);
        $ndt = $this->std($wo, 'ndt', $c); $cad = $this->std($wo, 'cad', $c);
        $this->assertCount(0, $this->rows($wo));
        $wo->update(['approve_at' => now()]);
        $this->assertCount(0, $this->rows($wo));
        $wo->update(['approve_at' => null]);
        $qa = $this->inspection($wo);
        $rows = $this->rows($wo);
        $this->assertCount(1, $rows); $this->assertSame('NDT', $rows->first()['process_name']);
        $this->assertSame(2, $rows->first()['qty']);
        $qa->update(['ignore_row' => true]); $this->assertCount(0, $this->rows($wo));
        $qa->update(['ignore_row' => false]);
        $ndt->update(['date_start' => today(), 'date_finish' => today()]);
        $this->assertCount(0, $this->rows($wo));
        $wo->update(['approve_at' => now()]);
        $this->assertSame('CAD', $this->rows($wo)->sole()['process_name']);
        $cad->update(['ignore_row' => true]); $this->assertCount(0, $this->rows($wo));
    }

    public function test_part_sequence_and_finished_or_assigned_rows_are_not_ready(): void
    {
        $wo = $this->createWorkorder(); $first = $this->part($wo); $next = $this->part($wo, 'Nickel', 2, $first->tdr);
        $this->assertCount(0, $this->rows($wo));
        $wo->update(['approve_at' => now()]);
        $this->assertSame('Chrome strip', $this->rows($wo)->sole()['process_name']);
        $first->update(['date_start' => today()]); $this->assertCount(0, $this->rows($wo));
        $first->update(['date_finish' => today()]); $this->assertSame('Nickel', $this->rows($wo)->sole()['process_name']);
        $next->update(['repair_order' => 'EXTERNAL-RO']); $this->assertCount(0, $this->rows($wo));
        $next->update(['repair_order' => null]);
        $wo->forceFill(['done_at' => today()])->save(); $this->assertCount(0, $this->rows($wo));
    }

    public function test_shipping_check_ro_numbering_snapshot_duplicate_protection_and_cancel(): void
    {
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $wo = $this->createWorkorder(['approve_at' => now(), 'user_id' => $user->id]); $part = $this->part($wo);
        $items = $this->items($this->rows($wo)); $vendor = Vendor::create(['name' => 'PV vendor']);
        $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id])->assertUnprocessable();
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertOk();
        $this->assertTrue($this->rows($wo)->sole()['checked']);
        $this->assertNull($part->fresh()->date_start);
        $response = $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id, 'vendor_address' => 'Address <script>unsafe</script>'])->assertOk();
        $order = ProcessVendorRepairOrder::latest('id')->firstOrFail();
        $this->assertMatchesRegularExpression('/^AV-RO-\d{6,}$/', $order->number);
        $this->assertNull($part->fresh()->repair_order); $this->assertNull($part->fresh()->date_start);
        $this->get($response->json('url'))->assertOk()->assertSee('PV-PART-'.$wo->id)->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false);
        $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id])->assertUnprocessable();
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => false])->assertUnprocessable();
        $part->update(['in_traveler' => true, 'traveler_group' => 7]);
        $regrouped = $this->rows($wo);
        $this->assertSame($order->id, $regrouped->sole()['repair_order_id']);
        $this->postJson(route('process-vendor.check'), ['items' => $this->items($regrouped), 'checked' => true])->assertUnprocessable();
        $part->update(['in_traveler' => false, 'traveler_group' => null]);
        $this->postJson(route('process-vendor.orders.cancel', $order))->assertOk();
        $this->assertNull($this->rows($wo)->sole()['repair_order_id']);
        $this->assertNotNull($order->fresh()->cancelled_at);
        $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id])->assertOk();
        $this->assertNotSame($order->number, ProcessVendorRepairOrder::latest('id')->first()->number);
    }

    public function test_ro_keeps_vendor_contact_snapshot_and_allows_order_only_overrides(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder(['approve_at' => now()]); $this->part($wo);
        $items = $this->items($this->rows($wo));
        $vendor = Vendor::create(['name' => 'Snapshot vendor', 'address' => 'Original address',
            'contact_name' => 'Original contact', 'phone' => '555-0100', 'email' => 'original@example.test',
            'vendor_number' => 'V-99', 'ship_via' => 'Original courier', 'terms' => 'Net 30']);
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertOk();
        $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id])->assertOk();
        $order = ProcessVendorRepairOrder::latest('id')->firstOrFail();
        $snapshot = $order->document;
        $this->assertSame('Original address', $snapshot['vendor_address']);
        $this->assertSame('original@example.test', $snapshot['vendor_email']);
        $this->assertSame('Original contact', $snapshot['vendor_contact_name']);
        $this->assertSame('V-99', $snapshot['vendor_number']);
        $vendor->update(['name' => 'Changed vendor', 'address' => 'Changed address', 'phone' => 'Changed phone',
            'contact_name' => 'Changed contact', 'email' => 'changed@example.test', 'terms' => 'Net 60']);
        $this->assertSame($snapshot, $order->fresh()->document);
        $this->get(route('process-vendor.orders.show', $order))->assertOk()->assertSee('Original contact')
            ->assertSee('original@example.test')->assertSee('555-0100')->assertDontSee('Changed address');
        $this->postJson(route('process-vendor.orders.cancel', $order))->assertOk();
        $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id,
            'vendor_address' => 'Order-only address', 'vendor_phone' => '', 'vendor_email' => 'ro@example.test'])->assertOk();
        $next = ProcessVendorRepairOrder::latest('id')->firstOrFail();
        $this->assertSame('Order-only address', $next->document['vendor_address']);
        $this->assertSame('', $next->document['vendor_phone']);
        $this->assertSame('ro@example.test', $next->document['vendor_email']);
        $this->assertSame('Net 60', $next->document['terms']);
        $this->assertSame('Changed address', $vendor->fresh()->address);
        $this->assertSame('Changed phone', $vendor->fresh()->phone);
    }

    public function test_changed_quantity_or_approval_rejects_stale_submission_atomically(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder(['approve_at' => now()]); $one = $this->part($wo); $two = $this->part($wo, 'CAD');
        $items = $this->items($this->rows($wo));
        $two->tdr->update(['qty' => 1]);
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertUnprocessable();
        $this->assertSame(0, ProcessVendorCheck::where('workorder_id', $wo->id)->count());
        $items = $this->items($this->rows($wo));
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertOk();
        $one->tdr->update(['qty' => 1]);
        $this->assertFalse($this->rows($wo)->firstWhere('part_number', $one->tdr->component->part_number)['checked']);
        $wo->update(['approve_at' => null]);
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertUnprocessable();
    }

    public function test_traveler_is_one_shipping_unit_and_cannot_skip_previous_operation(): void
    {
        $wo = $this->createWorkorder(['approve_at' => now()]); $first = $this->part($wo);
        $a = $this->part($wo, 'Chrome strip', 2, $first->tdr); $b = $this->part($wo, 'Chrome', 3, $first->tdr);
        $a->update(['in_traveler' => true, 'traveler_group' => 1]); $b->update(['in_traveler' => true, 'traveler_group' => 1]);
        $this->assertSame('Chrome strip', $this->rows($wo)->sole()['process_name']);
        $first->update(['date_start' => today(), 'date_finish' => today()]);
        $row = $this->rows($wo)->sole(); $this->assertSame('Traveler', $row['process_name']);
        $this->assertStringContainsString('Chrome strip', $row['specification']);
        $a->update(['date_start' => today()]); $this->assertCount(0, $this->rows($wo));
    }

    public function test_bushing_route_checks_its_own_preceding_operations(): void
    {
        $wo = $this->createWorkorder(['approve_at' => now()]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $c = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'BUSH', 'name' => 'Bushing', 'ipl_num' => '1-1']);
        $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id, 'component_id' => $c->id, 'qty' => 2, 'qty_remaining' => 2, 'selection_active' => true]);
        $processes = [];
        foreach (['Machining', 'NDT', 'Anodizing'] as $name) {
            $pn = ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => $name, 'form_number' => 'PV']);
            $spec = Process::create(['process_names_id' => $pn->id, 'process' => 'SPEC '.$name]);
            $processes[] = WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $spec->id, 'qty' => 2]);
        }
        $this->assertSame('Machining', $this->rows($wo)->sole()['process_name']);
        $processes[0]->update(['date_start' => today(), 'date_finish' => today()]);
        $this->assertSame('NDT', $this->rows($wo)->sole()['process_name']);
        $line->update(['selection_active' => false]); $this->assertCount(0, $this->rows($wo));
    }

    public function test_page_and_mutations_require_server_side_feature_access(): void
    {
        $tech = $this->createUserWithRole('Technician'); $this->actingAs($tech);
        $this->get(route('process-vendor.index'))->assertForbidden();
        $this->getJson(route('process-vendor.queue'))->assertForbidden();
        $this->postJson(route('process-vendor.refresh'))->assertForbidden();
        $this->getJson(route('process-vendor.orders.index'))->assertForbidden();
        $this->postJson(route('process-vendor.check'), [])->assertForbidden();
        $this->postJson(route('process-vendor.orders.store'), [])->assertForbidden();
        $this->postJson(route('process-vendor.preferences'), ['processes' => []])->assertForbidden();
        $this->grantFeatureAccess($tech, 'process_vendor');
        $this->get(route('process-vendor.index'))->assertOk()->assertSee('Shipping selection');
    }

    public function test_cached_queue_is_read_only_and_does_not_recalculate_on_http_requests(): void
    {
        config(['cache.default' => 'array']);
        $this->actingAs($this->createUserWithRole('Admin'));
        for ($i = 0; $i < 4; $i++) {
            $wo = $this->createWorkorder(['approve_at' => now()]); $this->part($wo);
        }
        $cache = app(\App\Services\ProcessVendorCache::class);
        $this->assertSame(4, $cache->refresh()['rebuilt']);
        $this->assertSame(0, $cache->refresh()['rebuilt']);
        $this->partialMock(ProcessVendorQueue::class, function ($mock) { $mock->shouldNotReceive('rows'); });
        $this->getJson(route('process-vendor.queue'))->assertOk()->assertJsonCount(4, 'rows')->assertJsonPath('cache.ready', true);
        $this->get(route('process-vendor.index'))->assertOk()->assertDontSee('class="pv-workorder" open', false);
    }

    public function test_global_search_includes_hidden_processes_and_manual_refresh_rebuilds_changes(): void
    {
        config(['cache.default' => 'array']);
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder(['approve_at' => now()]); $part = $this->part($wo, 'Paint');
        $this->postJson(route('process-vendor.preferences'), ['processes' => [], 'groups' => []])->assertOk();
        $url = route('process-vendor.refresh');
        $first = $this->postJson($url)->assertOk()->assertJsonPath('refresh.rebuilt', 1)
            ->assertJsonCount(0, 'rows')->assertJsonCount(1, 'search_rows');
        $this->assertSame('Paint', trim($first->json('search_rows.0.process_name')));
        $this->assertStringContainsString('pv-wo-processes', $first->json('html'));
        $this->postJson($url)->assertOk()->assertJsonPath('refresh.rebuilt', 0);
        $part->tdr->update(['qty' => 7]);
        $this->getJson(route('process-vendor.queue'))->assertOk()->assertJsonPath('search_rows.0.qty', 2);
        $latest = $this->postJson($url)->assertOk()->assertJsonPath('refresh.rebuilt', 1)->assertJsonPath('search_rows.0.qty', 7);
        $this->postJson(route('process-vendor.check'), ['items' => $this->items(collect($latest->json('search_rows'))), 'checked' => true, 'search_all' => true])->assertOk();
    }

    public function test_manual_refresh_reports_running_refresh_and_retains_previous_cache_on_failure(): void
    {
        config(['cache.default' => 'array']);
        $this->actingAs($this->createUserWithRole('Admin'));
        $cache = app(\App\Services\ProcessVendorCache::class);
        $key = 'process-vendor:v3:'.hash('sha256', \Illuminate\Support\Facades\DB::connection()->getDatabaseName()).':refresh-lock';
        $lock = \Illuminate\Support\Facades\Cache::lock($key, 30); $this->assertTrue($lock->get());
        try { $this->postJson(route('process-vendor.refresh'))->assertStatus(202)->assertJsonPath('refresh.busy', true); }
        finally { $lock->release(); }
        $wo = $this->createWorkorder(['approve_at' => now()]); $this->part($wo);
        $cache->refresh();
        $before = $cache->read()['cache']['updated_at'];
        $this->partialMock(ProcessVendorQueue::class, function ($mock) { $mock->shouldReceive('rows')->andThrow(new \RuntimeException('Test refresh failure')); });
        \Illuminate\Support\Facades\DB::table('workorders')->where('id', $wo->id)->update(['approve_at' => now()->subMinute()]);
        $this->postJson(route('process-vendor.refresh'))->assertStatus(503);
        $this->assertSame($before, $cache->read()['cache']['updated_at']);
        $this->assertFalse($cache->read()['cache']['refreshing']);
    }

    public function test_cache_detects_bulk_edits_deletions_and_catalog_changes(): void
    {
        config(['cache.default' => 'array']);
        $one = $this->createWorkorder(['approve_at' => now()]); $a = $this->part($one);
        $two = $this->createWorkorder(['approve_at' => now()]); $b = $this->part($two);
        $cache = app(\App\Services\ProcessVendorCache::class);
        $this->assertSame(2, $cache->refresh()['rebuilt']);
        // Bulk SQL deliberately bypasses timestamps and Eloquent observers.
        \Illuminate\Support\Facades\DB::table('tdr_processes')->where('id', $a->id)->update(['date_start' => today()]);
        $this->assertSame(1, $cache->refresh()['rebuilt']);
        $this->assertCount(1, $cache->read()['rows']);
        \Illuminate\Support\Facades\DB::table('tdr_processes')->where('id', $a->id)->delete();
        $this->assertSame(1, $cache->refresh()['rebuilt']);
        $b->processName->update(['name' => 'Paint']);
        $this->assertSame(2, $cache->refresh()['rebuilt']);
        $this->assertCount(1, $cache->read()['rows']);
        $two->forceFill(['done_at' => today()])->save();
        $cache->refresh();
        $this->assertSame(1, $cache->read()['cache']['workorders']);
    }

    public function test_hidden_default_processes_do_not_release_their_successor(): void
    {
        foreach (['Bake', 'Machining (AT)', 'Machining (EC)', 'PAINT', 'Stress Relief', 'Outcoming inspection', 'Outgoing Inspection'] as $name) {
            $wo = $this->createWorkorder(['approve_at' => now()]);
            $first = $this->part($wo, $name);
            $this->part($wo, 'Nickel', 2, $first->tdr);
            $this->assertTrue(ProcessVendorQueue::hiddenByDefault($name), $name);
            $this->assertSame($first->processName->name, $this->rows($wo)->sole()['process_name']);
            $first->update(['date_start' => today(), 'date_finish' => today()]);
            $this->assertSame('Nickel', $this->rows($wo)->sole()['process_name']);
        }
    }

    public function test_partial_workorder_submission_and_new_unselected_parts_are_rejected(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder(['approve_at' => now()]);
        $this->part($wo); $this->part($wo);
        $items = $this->items($this->rows($wo));
        $this->postJson(route('process-vendor.check'), ['items' => [$items[0]], 'checked' => true])->assertUnprocessable();
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertOk();
        $this->part($wo);
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertUnprocessable();
    }

    public function test_ndt_variants_are_independent_workorder_selections_and_user_specific_tabs(): void
    {
        config(['cache.default' => 'array']);
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $wo = $this->createWorkorder(['approve_at' => now()]);
        foreach (['NDT-1', 'NDT-4', 'Eddy Current', 'Bake', 'EC'] as $name) $this->part($wo, $name);
        $rows = $this->rows($wo);
        $this->assertCount(3, $rows->where('process_group', 'NDT'));
        $this->postJson(route('process-vendor.check'), ['items' => $this->items($rows->where('process_name', 'NDT-1')->values()), 'checked' => true])->assertOk();
        $this->postJson(route('process-vendor.check'), ['items' => $this->items($rows->where('process_group', 'NDT')->values()), 'checked' => true])->assertOk();
        app(\App\Services\ProcessVendorCache::class)->refresh();
        $this->getJson(route('process-vendor.queue'))->assertOk()->assertJsonCount(3, 'rows');
        $prefs = app(\App\Services\ProcessVendorPreferences::class)->forUser($user->id);
        $this->assertContains('NDT', $prefs['enabled']->all());
        $this->assertNotContains('EC', $prefs['enabled']->all());
        $this->assertNotContains('Bake', $prefs['enabled']->all());
        foreach (['NDT-1', 'NDT-4', 'Eddy Current'] as $name) $this->assertContains($name, $prefs['enabled']->all());
        $this->postJson(route('process-vendor.preferences'), ['processes' => ['Bogus']])->assertUnprocessable();
        $this->postJson(route('process-vendor.preferences'), ['processes' => ['Bake']])->assertOk();
        $this->getJson(route('process-vendor.queue'))->assertOk()->assertJsonCount(1, 'rows')->assertJsonPath('rows.0.process_name', 'Bake');
        $other = $this->createUserWithRole('Admin');
        $this->assertContains('NDT', app(\App\Services\ProcessVendorPreferences::class)->forUser($other->id)['enabled']->all());
        $this->assertNotContains('Bake', app(\App\Services\ProcessVendorPreferences::class)->forUser($other->id)['enabled']->all());
        \App\Models\UserUiSetting::create(['user_id' => $other->id, 'scope' => 'process-vendor.index', 'key' => 'visible_processes', 'value' => []]);
        $this->assertSame([], app(\App\Services\ProcessVendorPreferences::class)->forUser($other->id)['enabled']->all());
        $this->postJson(route('process-vendor.preferences'), ['processes' => []])->assertOk()->assertJsonCount(0, 'rows')->assertJsonCount(0, 'preferences.enabled');
    }

    public function test_custom_vendor_group_merges_wo_and_preserves_original_ro_operations(): void
    {
        config(['cache.default' => 'array']);
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $wo = $this->createWorkorder(['approve_at' => now()]);
        $this->part($wo, 'CAD'); $this->part($wo, 'Cad stripping'); $this->part($wo, 'Cad plate');
        $base = $this->rows($wo); $members = $base->pluck('process_group')->unique()->values()->all();
        app(\App\Services\ProcessVendorCache::class)->refresh();
        $response = $this->postJson(route('process-vendor.preferences'), ['processes' => $members,
            'groups' => [['name' => 'CAD', 'members' => $members]]])->assertOk()->assertJsonCount(1, 'preferences.enabled')->assertJsonCount(3, 'rows');
        $rows = collect($response->json('rows'));
        $this->assertSame(['CAD'], $rows->pluck('process_group')->unique()->all());
        $this->assertSame($base->pluck('fingerprint')->sort()->values()->all(), $rows->pluck('fingerprint')->sort()->values()->all());
        $this->get(route('process-vendor.index'))->assertOk()->assertSee('CAD');
        $this->postJson(route('process-vendor.check'), ['items' => $this->items($rows->take(1)), 'checked' => true])->assertUnprocessable();
        $items = $this->items($rows);
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertOk();
        $vendor = Vendor::create(['name' => 'Group vendor']);
        $this->postJson(route('process-vendor.orders.store'), ['items' => $items, 'vendor_id' => $vendor->id])->assertOk();
        $order = ProcessVendorRepairOrder::latest('id')->firstOrFail();
        $this->assertSame($base->pluck('process_name')->sort()->values()->all(), collect($order->document['items'])->pluck('process_name')->sort()->values()->all());
        $other = $this->createUserWithRole('Admin');
        $this->assertCount(0, app(\App\Services\ProcessVendorPreferences::class)->forUser($other->id)['groups']);
        $this->postJson(route('process-vendor.preferences'), ['processes' => $members, 'groups' => []])->assertOk()->assertJsonCount(0, 'preferences.groups');
    }

    public function test_group_subprocess_requires_complete_leaf_and_reserves_only_that_process(): void
    {
        config(['cache.default' => 'array']);
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder(['approve_at' => now()]);
        $this->part($wo, 'Cad stripping'); $this->part($wo, 'Cad stripping'); $this->part($wo, 'Cad plate');
        app(\App\Services\ProcessVendorCache::class)->refresh();
        $response = $this->postJson(route('process-vendor.preferences'), ['processes' => [],
            'groups' => [['name' => 'CAD vendor', 'members' => ['Cad stripping', 'Cad plate']]]])->assertOk();
        $rows = collect($response->json('rows'));
        $leaf = $this->items($rows->where('process_name', 'Cad stripping')->values());
        $scope = ['selection_process' => 'Cad stripping', 'checked' => true];
        $this->postJson(route('process-vendor.check'), $scope + ['items' => [$leaf[0]]])->assertUnprocessable();
        $this->postJson(route('process-vendor.check'), $scope + ['items' => $this->items($rows)])->assertUnprocessable();
        $this->postJson(route('process-vendor.check'), $scope + ['items' => $leaf])->assertOk();
        $current = $this->rows($wo);
        $this->assertCount(2, $current->where('checked', true));
        $this->assertFalse($current->firstWhere('process_name', 'Cad plate')['checked']);
        $vendor = Vendor::create(['name' => 'Leaf vendor']);
        $orderData = ['vendor_id' => $vendor->id, 'selection_process' => 'Cad stripping', 'items' => $leaf];
        $this->postJson(route('process-vendor.orders.store'), array_replace($orderData, ['selection_process' => 'Cad plate']))->assertUnprocessable();
        $this->postJson(route('process-vendor.orders.store'), $orderData)->assertOk();
        $order = ProcessVendorRepairOrder::latest('id')->firstOrFail();
        $this->assertCount(2, $order->document['items']);
        $this->assertSame(['Cad stripping'], collect($order->document['items'])->pluck('process_name')->unique()->values()->all());
        $this->assertNull($this->rows($wo)->firstWhere('process_name', 'Cad plate')['repair_order_id']);
        $this->postJson(route('process-vendor.orders.cancel', $order))->assertOk();
        $this->assertCount(0, $this->rows($wo)->whereNotNull('repair_order_id'));
    }

    public function test_ndt_catalog_exposes_individual_processes_for_manual_grouping(): void
    {
        $user = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['approve_at' => now()]);
        $this->part($wo, 'NDT 1'); $this->part($wo, 'NDT 4'); $this->part($wo, 'Eddy Current');
        $options = app(\App\Services\ProcessVendorPreferences::class)->forUser($user->id)['options'];
        foreach (['NDT 1', 'NDT 4', 'Eddy Current'] as $name) $this->assertSame([$name], $options->firstWhere('name', $name)['processes']);
    }

    public function test_legacy_ndt_preferences_expand_once_and_manual_groups_preserve_fingerprints(): void
    {
        config(['cache.default' => 'array']);
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $wo = $this->createWorkorder(['approve_at' => now()]);
        foreach (['NDT', 'NDT-1', 'NDT-4', 'Eddy Current'] as $name) $this->part($wo, $name);
        $service = app(\App\Services\ProcessVendorPreferences::class);
        \App\Models\UserUiSetting::create(['user_id' => $user->id, 'scope' => 'process-vendor.index', 'key' => 'visible_processes', 'value' => ['NDT']]);
        \App\Models\UserUiSetting::create(['user_id' => $user->id, 'scope' => 'process-vendor.index', 'key' => 'process_groups',
            'value' => [['name' => 'Inspection vendor', 'enabled' => false, 'members' => ['NDT']]]]);
        $prefs = $service->forUser($user->id);
        foreach (['NDT', 'NDT-1', 'NDT-4', 'Eddy Current'] as $name) {
            $this->assertContains($name, $prefs['visible']->all());
            $this->assertContains($name, $prefs['groups']->first()['members']);
        }
        $raw = $this->rows($wo); // Existing cached NDT bucket and readiness fingerprint must remain compatible.
        $split = $service->apply($raw, $prefs);
        $this->assertCount(4, $split->pluck('process_group')->unique());
        $this->assertSame($raw->pluck('fingerprint')->all(), $split->pluck('fingerprint')->all());
        $this->postJson(route('process-vendor.check'), ['items' => $this->items($split->where('process_name', 'NDT-1')->values()), 'checked' => true])->assertOk();
        $this->postJson(route('process-vendor.preferences'), ['processes' => ['NDT']])->assertOk();
        $saved = $service->forUser($user->id);
        $this->assertSame(['NDT'], $saved['visible']->all()); // Must not expand again after Save.
        $this->assertContains('NDT-4', $saved['groups']->first()['members']); // Omitted groups retain expanded membership.
        $this->postJson(route('process-vendor.preferences'), ['processes' => ['Eddy Current'],
            'groups' => [['name' => 'My NDT', 'members' => ['NDT-1', 'NDT-4'], 'enabled' => true]]])->assertOk();
        $grouped = $service->apply($this->rows($wo), $service->forUser($user->id));
        $this->assertCount(2, $grouped->where('process_group', 'My NDT'));
        $this->assertCount(1, $grouped->where('process_group', 'Eddy Current'));
        $this->assertTrue($grouped->firstWhere('process_name', 'NDT-1')['checked']);
    }

    public function test_defaults_restore_the_captured_local_groups_only_when_saved(): void
    {
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $preset = config('process_vendor.defaults');
        $names = collect($preset['visible'])->merge(collect($preset['groups'])->pluck('members')->flatten())->unique();
        foreach ($names as $name) {
            if ($name !== 'Traveler') ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => $name, 'form_number' => 'PV', 'show_in_process_picker' => true]);
        }
        $this->postJson(route('process-vendor.preferences'), ['processes' => ['Traveler'], 'groups' => []])->assertOk();
        $service = app(\App\Services\ProcessVendorPreferences::class);
        $prefs = $service->forUser($user->id);
        $this->assertSame(['Traveler'], $prefs['visible']->all());
        $this->assertCount(0, $prefs['groups']);
        $this->assertSame($preset, $prefs['defaults']);
        $this->postJson(route('process-vendor.preferences'), ['processes' => $prefs['defaults']['visible'], 'groups' => $prefs['defaults']['groups']])->assertOk();
        $saved = $service->forUser($user->id);
        $this->assertSame($preset['groups'], $saved['groups']->all());
        $this->assertEqualsCanonicalizing(['CAD', 'Anod', 'CHrome', 'Nickel', 'Silver', 'NDT', 'Passivation', 'Shimmy Damper O/H', 'Shot peening', 'Traveler'], $saved['enabled']->all());
        $this->assertNotContains('US Measurement', collect($saved['groups'])->firstWhere('name', 'NDT')['members']);
        $this->assertContains('S - Nickel Machining', collect($saved['groups'])->firstWhere('name', 'Nickel')['members']);
    }

    public function test_initial_page_embeds_own_saved_filters_before_revealing_queue(): void
    {
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $filters = ['process' => 'CAD', 'subProcess' => 'Cad stripping', 'query' => '107410'];
        \App\Models\UserUiSetting::create(['user_id' => $user->id, 'scope' => 'process-vendor.index', 'key' => 'filters', 'value' => $filters]);
        $other = $this->createUserWithRole('Admin');
        \App\Models\UserUiSetting::create(['user_id' => $other->id, 'scope' => 'process-vendor.index', 'key' => 'filters', 'value' => ['query' => 'other-user-only']]);
        $this->get(route('process-vendor.index'))->assertOk()->assertViewHas('filters', $filters)
            ->assertSee('data-pv-initializing', false)->assertSee('aria-busy="true"', false)->assertDontSee('other-user-only');
    }

    public function test_invalid_custom_groups_do_not_partially_save_preferences(): void
    {
        $user = $this->createUserWithRole('Admin'); $this->actingAs($user);
        $wo = $this->createWorkorder(['approve_at' => now()]); $this->part($wo, 'CAD'); $this->part($wo, 'Nickel');
        $this->postJson(route('process-vendor.preferences'), ['processes' => ['CAD'], 'groups' => []])->assertOk();
        foreach ([
            [['name' => 'A', 'members' => ['CAD']], ['name' => 'a', 'members' => ['Nickel']]],
            [['name' => 'A', 'members' => ['CAD']], ['name' => 'B', 'members' => ['CAD']]],
            [['name' => 'CAD', 'members' => ['Nickel']]],
            [['name' => 'A', 'members' => ['Not a process']]],
            [['name' => 'A', 'members' => []]],
        ] as $groups) {
            $this->postJson(route('process-vendor.preferences'), ['processes' => ['Nickel'], 'groups' => $groups])->assertUnprocessable();
            $prefs = app(\App\Services\ProcessVendorPreferences::class)->forUser($user->id);
            $this->assertSame(['CAD'], $prefs['visible']->all());
            $this->assertCount(0, $prefs['groups']);
        }
    }

    public function test_group_tab_visibility_is_independent_and_never_duplicates_members(): void
    {
        config(['cache.default' => 'array']);
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder(['approve_at' => now()]);
        $this->part($wo, 'CAD'); $this->part($wo, 'Cad stripping');
        app(\App\Services\ProcessVendorCache::class)->refresh();
        $group = ['name' => 'CAD', 'members' => ['CAD', 'Cad stripping'], 'enabled' => true];
        $this->postJson(route('process-vendor.preferences'), ['processes' => [], 'groups' => [$group]])
            ->assertOk()->assertJsonCount(2, 'rows')->assertJsonPath('preferences.enabled', ['CAD']);
        $response = $this->postJson(route('process-vendor.preferences'), ['processes' => $group['members'], 'groups' => [$group]])
            ->assertOk()->assertJsonCount(2, 'rows')->assertJsonPath('preferences.enabled', ['CAD']);
        $items = $this->items(collect($response->json('rows')));
        $this->postJson(route('process-vendor.check'), ['items' => array_slice($items, 0, 1), 'checked' => true])->assertUnprocessable();
        $group['enabled'] = false;
        $this->postJson(route('process-vendor.preferences'), ['processes' => ['Cad stripping'], 'groups' => [$group]])
            ->assertOk()->assertJsonCount(1, 'rows')->assertJsonPath('preferences.enabled', ['Cad stripping'])
            ->assertJsonPath('preferences.groups.0.enabled', false)->assertJsonCount(2, 'preferences.groups.0.members');
        $this->postJson(route('process-vendor.check'), ['items' => $items, 'checked' => true])->assertUnprocessable();
        $this->postJson(route('process-vendor.preferences'), ['processes' => [], 'groups' => [$group]])
            ->assertOk()->assertJsonCount(0, 'rows')->assertJsonCount(0, 'preferences.enabled');
    }

    public function test_canonical_std_is_not_duplicated_and_read_does_not_build_stored_snapshots(): void
    {
        $wo = $this->createWorkorder(['instruction_id' => $this->createOverhaulInstruction()->id, 'scope_type' => Unit::SCOPE_FULL_UNIT]);
        $c = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'READONLY-STD', 'name' => 'Pin', 'ipl_num' => '1-10', 'units_assy' => 2, 'ndt_list' => true]);
        $ndt = $this->std($wo, 'ndt', $c); $this->inspection($wo);
        $carrier = Tdr::create(['workorder_id' => $wo->id, 'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER]);
        TdrProcess::create(['tdrs_id' => $carrier->id, 'process_names_id' => $ndt->process_name_id]);
        $before = \App\Models\WorkorderStdProcessItem::where('workorder_id', $wo->id)->get()->toArray();
        $writes = [];
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete|replace|alter)\b/i', $query->sql)) $writes[] = $query->sql;
        });
        $this->assertCount(1, $this->rows($wo));
        $this->assertSame([], $writes);
        $this->assertSame($before, \App\Models\WorkorderStdProcessItem::where('workorder_id', $wo->id)->get()->toArray());
    }

    public function test_std_alternative_part_numbers_share_one_quantity(): void
    {
        $wo = $this->createWorkorder(['instruction_id' => $this->createOverhaulInstruction()->id, 'scope_type' => Unit::SCOPE_FULL_UNIT]);
        $c = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'ALT-FIRST', 'name' => 'Pin', 'ipl_num' => '1-10A', 'units_assy' => 2, 'ndt_list' => true]);
        $this->std($wo, 'ndt', $c); $this->inspection($wo);
        Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'ALT-SECOND', 'name' => 'Pin', 'ipl_num' => '1-10B', 'units_assy' => 2, 'ndt_list' => true]);
        $row = $this->rows($wo)->sole();
        $this->assertSame(2, $row['qty']);
        $this->assertStringContainsString('ALT-FIRST', $row['part_number']);
        $this->assertStringContainsString('ALT-SECOND', $row['part_number']);
    }
}
