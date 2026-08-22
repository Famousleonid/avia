<?php

namespace Tests\Unit;

use App\Support\WoBushingProcessColumnKey;
use PHPUnit\Framework\TestCase;

class WoBushingProcessColumnKeyTest extends TestCase
{
    public function test_process_name_takes_priority_over_instruction_keywords(): void
    {
        $this->assertSame(
            'cad',
            WoBushingProcessColumnKey::resolve(
                'Cad plate',
                'MIL-STD-870. Bake for 23 hours at 350-400 F.'
            )
        );

        $this->assertSame(
            'stress_relief',
            WoBushingProcessColumnKey::resolve(
                'Bake (Stress relief)',
                'Prepare cadmium plated surfaces before baking.'
            )
        );
    }

    public function test_process_code_is_used_when_name_does_not_identify_a_column(): void
    {
        $this->assertSame(
            'cad',
            WoBushingProcessColumnKey::resolve('External process', 'CAD plating per MIL-STD-870')
        );
    }
}
