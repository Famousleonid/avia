<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProjectDateFormatTest extends TestCase
{
    public function test_project_date_formatter_uses_capitalized_month_abbreviation(): void
    {
        $this->assertSame('01/May/2026', format_project_date('2026-05-01'));
        $this->assertSame('01/May/2026', format_project_date('01/may/2026'));
        $this->assertSame('01/May/2026', format_project_date('01.MAY.2026'));
    }

    public function test_mains_tasks_date_picker_uses_project_display_format(): void
    {
        $view = view('admin.mains.partials.js.mains-general-tasks')->render();

        $this->assertStringContainsString('altFormat: "d/M/Y"', $view);
        $this->assertStringNotContainsString('altFormat: "d.M.y"', $view);
    }
}
