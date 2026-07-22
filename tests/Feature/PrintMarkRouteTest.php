<?php

namespace Tests\Feature;

use App\Models\PrintMark;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PrintMarkRouteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_print_mark_route_is_public_and_shows_encoded_data(): void
    {
        $printMark = PrintMark::query()->create([
            'token' => 'ABCD2345EFGH',
            'workorder_number' => 'W107736',
            'form_name' => 'Repair and Modification Record',
            'requirement_warnings' => ['FIG', 'ZONE'],
            'printed_by_name' => 'John Smith',
            'printed_at' => Carbon::create(2026, 5, 5, 9, 30),
        ]);

        $response = $this->get('/p/' . strtolower($printMark->token));

        $response
            ->assertOk()
            ->assertSee('INFO')
            ->assertSee('W107736')
            ->assertSee('Repair and Modification Record')
            ->assertSee('John Smith')
            ->assertSee('05/May/2026')
            ->assertSee('Missing required FIG and ZONE')
            ->assertSee('print-mark-warning')
            ->assertDontSee('password');
    }

    public function test_old_print_mark_route_does_not_expose_data_in_url(): void
    {
        $this->get('/W107736/john-smith/05May26/repair-and-modification-record')->assertNotFound();
    }

    public function test_print_mark_qr_partial_renders_small_unlabeled_marker(): void
    {
        $html = view('shared.print-mark.qr', [
            'printMarkWorkorder' => '107736',
            'printMarkPrintedBy' => 'ADMIN',
            'printMarkPrintedAt' => Carbon::create(2026, 5, 26),
            'printMarkFormName' => 'NDT',
            'printMarkWarnings' => ['FIG'],
        ])->render();

        $this->assertStringContainsString('system-print-qr', $html);
        $this->assertStringContainsString('width: 40px;', $html);
        $this->assertStringContainsString('data-screen-placement="page"', $html);
        $this->assertStringContainsString('Missing required FIG', $html);
        $this->assertTrue(str_contains($html, '<svg') || str_contains($html, 'api.qrserver.com'));
        $this->assertStringNotContainsString('System Print', $html);
        $this->assertDatabaseHas('print_marks', [
            'workorder_number' => 'W107736',
            'form_name' => 'NDT',
            'printed_by_name' => 'ADMIN',
        ]);
        $this->assertSame(
            ['FIG'],
            PrintMark::query()->latest('id')->firstOrFail()->requirement_warnings
        );
    }
}
