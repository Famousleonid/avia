<?php

namespace Tests\Unit;

use App\Tools\WorkorderTools\WorkorderToolRegistry;
use Tests\TestCase;

class WorkorderToolRegistryTest extends TestCase
{
    public function test_nlg_erj_sleeve_tool_supports_current_erj_manual_number(): void
    {
        $tools = app(WorkorderToolRegistry::class)->forManualNumber('32-21-01 ERJ');

        $this->assertSame(
            ['nlg-erj-170-sleeve-37'],
            collect($tools)->pluck('key')->all()
        );
    }

    public function test_nlg_erj_sleeve_tool_keeps_legacy_manual_number(): void
    {
        $tools = app(WorkorderToolRegistry::class)->forManualNumber('32-21-01');

        $this->assertSame(
            ['nlg-erj-170-sleeve-37'],
            collect($tools)->pluck('key')->all()
        );
    }
}
