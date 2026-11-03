<?php

namespace App\Themes;

use App\Models\Theme;
use Illuminate\Validation\ValidationException;

/**
 * A theme's settings are a flat list of fields declared by the theme:
 *
 *   {key, type: color|select|boolean|text, label, default, options?: list<string>, group?}
 *
 * The console builds the form from it and only stores values that fit it.
 */
class ThemeConfig
{
    public const TYPES = ['color', 'select', 'boolean', 'text'];

    /**
     * Every field at its default.
     *
     * @return array<string, mixed>
     */
    public function defaults(Theme $theme): array
    {
        $config = [];

        foreach ($theme->fields() as $field) {
            if ($this->isField($field)) {
                $default = $field['default'] ?? null;
                $config[$field['key']] = $this->valid($field, $default) ? $default : $this->fallback($field);
            }
        }

        return $config;
    }

    /**
     * Keep the declared keys only, fill the missing ones with defaults, and reject invalid values.
     *
     * @param  array<array-key, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(Theme $theme, array $input): array
    {
        $config = $this->defaults($theme);
        $errors = [];

        foreach ($theme->fields() as $field) {
            if (! $this->isField($field) || ! array_key_exists($field['key'], $input)) {
                continue;
            }

            $key = (string) $field['key'];
            $value = $field['type'] === 'boolean' ? filter_var($input[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) : $input[$key];

            if ($this->valid($field, $value)) {
                $config[$key] = $value;
            } else {
                $errors["config.{$key}"] = __('Choose a valid value.');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $config;
    }

    /**
     * Carry the values over to another version's fields (on upgrade).
     *
     * @param  array<array-key, mixed>  $current
     * @return array<string, mixed>
     */
    public function migrate(Theme $theme, array $current): array
    {
        $config = $this->defaults($theme);

        foreach ($theme->fields() as $field) {
            if ($this->isField($field) && array_key_exists($field['key'], $current) && $this->valid($field, $current[$field['key']])) {
                $config[(string) $field['key']] = $current[$field['key']];
            }
        }

        return $config;
    }

    /**
     * @param  array<array-key, mixed>  $field
     *
     * @phpstan-assert-if-true array{key: string, type: string, default?: mixed, options?: mixed} $field
     */
    private function isField(array $field): bool
    {
        return is_string($field['key'] ?? null) && in_array($field['type'] ?? null, self::TYPES, true);
    }

    /**
     * @param  array{key: string, type: string, default?: mixed, options?: mixed}  $field
     */
    private function valid(array $field, mixed $value): bool
    {
        return match ($field['type']) {
            'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1,
            'select' => is_string($value) && in_array($value, $this->options($field), true),
            'boolean' => is_bool($value),
            'text' => is_string($value) && mb_strlen($value) <= 200,
            default => false,
        };
    }

    /**
     * @param  array{key: string, type: string, default?: mixed, options?: mixed}  $field
     */
    private function fallback(array $field): mixed
    {
        return match ($field['type']) {
            'color' => '#000000',
            'select' => $this->options($field)[0] ?? '',
            'boolean' => false,
            default => '',
        };
    }

    /**
     * @param  array{key: string, type: string, default?: mixed, options?: mixed}  $field
     * @return list<string>
     */
    private function options(array $field): array
    {
        $options = $field['options'] ?? [];

        return is_array($options) ? array_values(array_filter($options, is_string(...))) : [];
    }
}
