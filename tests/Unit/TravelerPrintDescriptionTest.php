<?php

namespace Tests\Unit;

use App\Services\TravelerPrintDescription;
use PHPUnit\Framework\TestCase;

class TravelerPrintDescriptionTest extends TestCase
{
    public function test_duplicate_specification_and_name_are_removed_but_notes_survive(): void
    {
        $spec = 'Refer to SPM 20-00-10P16; Coverage ≥ 100%; Fig.6114.';
        $description = "Shot peening\r\nRefer to SPM 20-00-10P16;\nCoverage ≥ 100%; Fig.6114.\nMask bore.\nInspect after processing.";
        $this->assertSame("Mask bore.\nInspect after processing.", TravelerPrintDescription::additional($description, $spec, ['Shot peening']));
    }

    public function test_different_specifications_and_embedded_names_are_preserved(): void
    {
        $text = "Coverage ≥ 200%.\nDo not repeat Shot peening.\nFig.6115";
        $this->assertSame($text, TravelerPrintDescription::additional($text, 'Coverage ≥ 100%.', ['Shot peening']));
    }

    public function test_name_qualifiers_survive_and_plain_name_is_removed(): void
    {
        $this->assertSame('(Outer and Inner Diameter)', TravelerPrintDescription::additional("Chrome plating (Outer and Inner Diameter)\nSPEC", 'SPEC', ['Chrome plating']));
        $this->assertSame('', TravelerPrintDescription::additional("NDT\nRefer to ASTM E 1444", 'Refer to ASTM E 1444', ['NDT', 'NDT-1']));
    }

    public function test_additional_instruction_line_breaks_are_preserved(): void
    {
        $this->assertSame("Mask bore.\n\nInspect after processing.", TravelerPrintDescription::additional(
            "Shot peening\nSpecification line 1\nSpecification line 2\nMask bore.\n\nInspect after processing.",
            "Specification line 1\nSpecification line 2", ['Shot peening']));
    }

    public function test_inline_manual_addition_survives_and_limits_are_not_approximated(): void
    {
        $this->assertSame('4443 4444', TravelerPrintDescription::additional('Fig.6085, Fig.6084 4443 4444', 'Fig.6085, Fig.6084', []));
        $this->assertSame('Hold 40 hours', TravelerPrintDescription::additional('Hold 40 hours', 'Hold 4', []));
        $this->assertSame('Full requirements', TravelerPrintDescription::additional('Full requirements', 'Per Traveler template; full specification in Description.', []));
    }
}
