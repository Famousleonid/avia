<?php

namespace Tests\Unit;

use App\Services\TravelerPrintDescription;
use PHPUnit\Framework\TestCase;

class TravelerFigureDescriptionTest extends TestCase
{
    public function test_template_storage_keeps_reference_duplicated_in_catalog(): void
    {
        $this->assertSame('CMM Fig. 6128.', TravelerPrintDescription::additional(
            'CMM Fig. 6128.', 'CMM Fig. 6128.', [], preserveFigureReferences: true
        ));
        $this->assertSame('', TravelerPrintDescription::additional('CMM Fig. 6128.', 'CMM Fig. 6128.', []));
    }

    public function test_other_instructions_and_line_breaks_are_preserved(): void
    {
        $this->assertSame("Mask bore.\nFig. 6101, Fig. 6128.", TravelerPrintDescription::additional(
            "Mask bore.\nFig. 6101, Fig. 6128.", 'Fig. 6101, Fig. 6128.', [], true
        ));
        $this->assertSame('Fig. 6128.', TravelerPrintDescription::additional(
            "Machining\nCMM task 123.\nFig. 6128.", 'CMM task 123.', ['Machining (AT)'], true
        ));
    }
}
