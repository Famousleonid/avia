<?php

namespace App\Tools\WorkorderTools;

interface WorkorderToolDefinition
{
    public function key(): string;

    public function label(): string;

    public function manualNumbers(): array;

    public function toArray(): array;
}
