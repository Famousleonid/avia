<?php

namespace App\Services;

class ProcessAccessDecision
{
    public function __construct(
        public readonly bool $allowed,
        public readonly ?string $message = null,
        public readonly ?string $reason = null,
        public readonly array $contacts = [],
    ) {
    }

    public static function allow(): self
    {
        return new self(true);
    }

    public static function deny(string $message, ?string $reason = null, array $contacts = []): self
    {
        return new self(false, $message, $reason, $contacts);
    }
}
