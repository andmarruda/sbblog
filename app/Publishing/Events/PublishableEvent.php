<?php

namespace App\Publishing\Events;

/**
 * An event in the publishing log. Each type has a JSON Schema at
 * resources/schemas/events/{type}.v{version}.schema.json describing its payload.
 */
abstract class PublishableEvent
{
    abstract public static function type(): string;

    public static function schemaVersion(): int
    {
        return 1;
    }

    /**
     * Schema file, relative to resources/schemas.
     */
    public static function schema(): string
    {
        return 'events/'.static::type().'.v'.static::schemaVersion().'.schema.json';
    }

    abstract public function aggregateType(): string;

    abstract public function aggregateId(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function payload(): array;
}
