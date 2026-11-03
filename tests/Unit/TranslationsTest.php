<?php

namespace Tests\Unit;

use App\Apps\Scopes;
use App\Authorization\Permissions;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Every literal passed to t()/tc() in React or __() in PHP must exist in each non-English
 * JSON file. Keys are the English source strings, so en-US needs no entries.
 */
class TranslationsTest extends TestCase
{
    private const LOCALES = ['pt-BR'];

    /**
     * Keys that are passed around as data (breadcrumbs, nav, layout titles) instead of literal t() calls.
     */
    private const DATA_KEY_PATTERNS = [
        '/\btitle:\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)*)\1/',
        '/\bdescription:\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)*)\1/',
        '/\blabel:\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)*)\1/',
        '/\blabel="([^"]+)"/',
    ];

    public function test_every_used_string_is_translated(): void
    {
        $root = dirname(__DIR__, 2);
        $keys = [...$this->reactKeys($root.'/resources/js'), ...$this->phpKeys($root.'/app'), ...$this->phpKeys($root.'/routes')];
        $keys = [...$keys, ...$this->catalogKeys()];
        // Skip PHP file keys such as "auth.failed" or "passwords.sent_if_exists".
        $keys = array_values(array_unique(array_filter($keys, fn (string $key) => ! preg_match('/^[a-z_]+\.[a-z_.]+$/', $key))));

        foreach (self::LOCALES as $locale) {
            /** @var array<string, string> $messages */
            $messages = json_decode((string) file_get_contents("{$root}/lang/{$locale}.json"), true, flags: JSON_THROW_ON_ERROR);

            $missing = array_values(array_diff($keys, array_keys($messages)));
            sort($missing);

            $this->assertSame([], $missing, "Missing {$locale} translations:\n".implode("\n", $missing));
        }
    }

    /**
     * Permission descriptions, groups and role names are shown through t() in the roles page.
     *
     * @return list<string>
     */
    private function catalogKeys(): array
    {
        $keys = [];

        foreach (Permissions::ALL as [$group, , , $description]) {
            $keys[] = $group;
            $keys[] = $description;
        }

        return [...$keys, ...array_values(Scopes::ALL)];
    }

    /**
     * @return list<string>
     */
    private function reactKeys(string $dir): array
    {
        $keys = [];

        foreach ($this->files($dir, ['ts', 'tsx']) as $file) {
            if (str_contains($file, '/components/ui/') || str_contains($file, '/routes/') || str_contains($file, '/actions/') || str_contains($file, '/wayfinder/')) {
                continue;
            }

            $source = (string) file_get_contents($file);

            preg_match_all('/\btc?\(\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)*)\1/s', $source, $matches);
            $keys = [...$keys, ...$matches[2]];

            foreach (self::DATA_KEY_PATTERNS as $pattern) {
                preg_match_all($pattern, $source, $matches);
                $keys = [...$keys, ...end($matches)];
            }
        }

        return array_map(fn (string $key) => stripcslashes($key), $keys);
    }

    /**
     * @return list<string>
     */
    private function phpKeys(string $dir): array
    {
        $keys = [];

        foreach ($this->files($dir, ['php']) as $file) {
            preg_match_all('/\b(?:__|trans_choice)\(\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)*)\1/s', (string) file_get_contents($file), $matches);
            $keys = [...$keys, ...array_map(fn (string $key) => stripslashes($key), $matches[2])];
        }

        return $keys;
    }

    /**
     * @param  list<string>  $extensions
     * @return list<string>
     */
    private function files(string $dir, array $extensions): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
