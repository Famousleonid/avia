<?php

namespace App\Models\Concerns;

/** Immutable identity belongs to a process_names ID; name is only a display label. */
trait HasProcessIdentity
{
    protected static function bootHasProcessIdentity(): void
    {
        static::creating(function ($process): void {
            $process->identity_name = self::initialIdentityName((string) $process->name);
        });
        static::updating(function ($process): void {
            // Identity cannot be edited through the process directory or fill().
            if ($process->isDirty('identity_name')) {
                $process->identity_name = $process->getRawOriginal('identity_name')
                    ?? $process->newQuery()->whereKey($process->id)->value('identity_name');
                $process->syncOriginalAttribute('identity_name');
            }
        });
    }

    public static function initialIdentityName(string $name): string
    {
        return match (preg_replace('/[^a-z0-9]/', '', strtolower($name))) {
            'machining', 'machiningat' => 'Machining',
            'machiningec' => 'Machining (EC)',
            default => trim($name),
        };
    }

    public function identityName(): string
    {
        if (! $this->exists) {
            return self::initialIdentityName((string) $this->name);
        }
        if (! array_key_exists('identity_name', $this->attributes)) {
            $this->attributes['identity_name'] = $this->newQuery()->whereKey($this->id)->value('identity_name');
            $this->syncOriginalAttribute('identity_name');
        }

        return (string) ($this->attributes['identity_name'] ?? '');
    }

    public function hasIdentity(string|array $identities): bool
    {
        return in_array($this->identityName(), array_map([self::class, 'initialIdentityName'], (array) $identities), true);
    }

    public function scopeWhereIdentityName($query, string $identity, string $operator = '=')
    {
        return $query->whereIn($query->getModel()->qualifyColumn('id'), $query->getModel()->newQuery()
            ->select('id')->where('identity_name', $operator, self::initialIdentityName($identity)));
    }

    public function scopeWhereIdentityNames($query, array $identities, bool $not = false)
    {
        $ids = $query->getModel()->newQuery()->select('id')->whereIn('identity_name',
            array_map([self::class, 'initialIdentityName'], $identities));
        return $not ? $query->whereNotIn($query->getModel()->qualifyColumn('id'), $ids)
            : $query->whereIn($query->getModel()->qualifyColumn('id'), $ids);
    }

    public static function identityIds(string|array $identities): array
    {
        return self::query()->whereIdentityNames((array) $identities)->pluck('id')
            ->map(fn ($id) => (int) $id)->all();
    }

    public function scopeWhereNotIdentityNames($query, array $identities)
    {
        return $this->scopeWhereIdentityNames($query, $identities, true);
    }
}
