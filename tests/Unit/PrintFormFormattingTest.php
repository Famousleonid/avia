<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PrintFormFormattingTest extends TestCase
{
    public function test_cmm_number_keeps_attached_suffix_and_omits_words_after_a_space(): void
    {
        $this->assertSame('32-21-04', format_cmm_number('32-21-04 Goodrich'));
        $this->assertSame('32-11-01RM', format_cmm_number('CMM 32-11-01RM'));
        $this->assertSame('32-11A-01R2', format_cmm_number('32-11A-01R2 Goodrich'));
        $this->assertSame('32-11-01', format_cmm_number('32-11-01 RM'));
        $this->assertSame('72.10.01', format_cmm_number('72.10.01 Manufacturer'));
        $this->assertSame('', format_cmm_number('Goodrich'));
    }

    public function test_process_semicolon_becomes_a_new_line(): void
    {
        $this->assertSame(
            "Remove Chrome Plating as per AMPS A401\nBake as per MIL-STD-1501",
            format_process_number('Remove Chrome Plating as per AMPS A401; Bake as per MIL-STD-1501')
        );
    }
}
