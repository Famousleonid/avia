<?php

namespace App\Tools\WorkorderTools;

class NlgErj170Sleeve37Tool implements WorkorderToolDefinition
{
    public function key(): string
    {
        return 'nlg-erj-170-sleeve-37';
    }

    public function label(): string
    {
        return 'NLG ERJ-170 Sleeve 37';
    }

    public function manualNumbers(): array
    {
        return ['32-21-01'];
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'manual_numbers' => $this->manualNumbers(),
            'view' => 'admin.tools.definitions.nlg-erj-170-sleeve-37',
            'image' => asset('workorder-tools/images/ERJ-170 bush 37.jpg'),
            'print_title' => 'Sleeve Print Sheet',
            'print_subtitle' => 'ERJ-170 / Tool 37',
            'inputs' => [
                [
                    'key' => 'r_out',
                    'label' => 'R out',
                    'hint' => 'Outer radius from the reference drawing.',
                    'placeholder' => '',
                    'step' => '0.001',
                    'default' => '0.0',
                ],
                [
                    'key' => 'r_in',
                    'label' => 'R in',
                    'hint' => 'Inner radius from the reference drawing.',
                    'placeholder' => '',
                    'step' => '0.001',
                    'default' => '0.000',
                ],
                [
                    'key' => 'd',
                    'label' => 'D',
                    'hint' => 'Chord height from the drawing.',
                    'placeholder' => '',
                    'step' => '0.001',
                    'default' => '0.0',
                ],
            ],
        ];
    }
}
