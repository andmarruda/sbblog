<?php

namespace App\Console\Commands;

use App\Migration\V1\Verifier;
use App\Models\Blog;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sbblog:verify-v1
    {connection : Database connection of the v1 install}
    {--workspace= : Slug of the workspace the blog was imported into}
    {--blog= : Slug of the imported blog}
    {--sample=5 : How many articles to spot-check}')]
#[Description('Compare a v1 database with the v2 blog it was imported into')]
class VerifyV1 extends Command
{
    public function handle(Verifier $verifier): int
    {
        $workspace = Workspace::query()->where('slug', (string) $this->option('workspace'))->first();
        $blog = $workspace?->blogs()->where('slug', (string) $this->option('blog'))->first();

        if (! $blog instanceof Blog) {
            $this->error('Blog not found: pass --workspace and --blog.');

            return self::INVALID;
        }

        $result = $verifier->run((string) $this->argument('connection'), $blog, max(0, (int) $this->option('sample')));

        $this->table(['Entity', 'v1', 'v2', ''], array_map(fn (array $row) => [$row['entity'], $row['v1'], $row['v2'], $row['ok'] ? 'ok' : 'MISSING'], $result['counts']));

        if ($result['sample'] !== []) {
            $this->line('Spot check');
            $this->table(['v1 ID', 'Title', 'v1 text', 'v2 text', 'v2 HTML', 'Similarity %'], array_map(fn (array $row) => [$row['id'], $row['title'], $row['v1_chars'], $row['v2_chars'], $row['html_chars'], $row['similarity']], $result['sample']));
        }

        if ($result['mismatches'] !== []) {
            $this->warn(count($result['mismatches']).' article(s) differ by more than '.(100 - Verifier::MIN_SIMILARITY).'% or have another title:');
            $this->table(['v1 ID', 'Title', 'Same title', 'Similarity %'], array_map(fn (array $row) => [$row['id'], $row['title'], $row['title_ok'] ? 'yes' : 'no', $row['similarity']], $result['mismatches']));
        }

        $ok = $result['mismatches'] === [] && collect($result['counts'])->every(fn (array $row) => $row['ok']);
        $ok ? $this->info('Everything from v1 is in v2.') : $this->error('Verification found differences.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
