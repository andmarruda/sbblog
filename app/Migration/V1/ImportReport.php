<?php

namespace App\Migration\V1;

/**
 * Counts per entity (created / existing / skipped) and the warnings a person should read.
 */
final class ImportReport
{
    /** @var array<string, array{created: int, existing: int, skipped: int}> */
    private array $counts = [];

    /** @var list<string> */
    private array $warnings = [];

    /**
     * @param  'created'|'existing'|'skipped'  $outcome
     */
    public function count(string $entity, string $outcome): void
    {
        $this->counts[$entity] ??= ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $this->counts[$entity][$outcome]++;
    }

    public function warn(string $message): void
    {
        $this->warnings[] = $message;
    }

    /**
     * @return array<string, array{created: int, existing: int, skipped: int}>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    public function created(string $entity): int
    {
        return $this->counts[$entity]['created'] ?? 0;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return array_values(array_unique($this->warnings));
    }

    /**
     * True when the import wrote anything a reader can see.
     */
    public function changed(): bool
    {
        foreach (['blog settings', 'categories', 'authors', 'articles', 'tags', 'comments', 'integrations', 'social links'] as $entity) {
            if ($this->created($entity) > 0) {
                return true;
            }
        }

        return false;
    }
}
