<?php

namespace App\Console\Commands;

use App\Migration\V1\Importer;
use App\Migration\V1\ImportOptions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('sbblog:import-v1
    {connection : Database connection of the v1 install (see the "v1" connection in config/database.php)}
    {--workspace= : Slug of the workspace to import into (created when missing)}
    {--blog= : Slug of the blog to import into (created when missing)}
    {--workspace-name= : Name for a new workspace (defaults to the v1 blog title)}
    {--owner= : Email of the owner of a new workspace (defaults to the first v1 user)}
    {--language=pt-BR : Language of the v1 articles}
    {--storage= : Path to v1\'s storage/app/public, to import covers and images}
    {--dry-run : Show what would be imported, then roll everything back}')]
#[Description('Import a v1 SBBlog database into a v2 blog (safe to run again)')]
class ImportV1 extends Command
{
    public function handle(Importer $importer): int
    {
        $workspace = (string) $this->option('workspace');
        $blog = (string) $this->option('blog');

        if ($workspace === '' || $blog === '') {
            $this->error('Pass --workspace and --blog.');

            return self::INVALID;
        }

        $storage = $this->option('storage');
        if (is_string($storage) && ! is_dir($storage)) {
            $this->error("The storage folder [{$storage}] does not exist.");

            return self::INVALID;
        }

        $options = new ImportOptions(
            connection: (string) $this->argument('connection'),
            workspace: $workspace,
            blog: $blog,
            language: (string) $this->option('language'),
            ownerEmail: is_string($this->option('owner')) ? $this->option('owner') : null,
            workspaceName: is_string($this->option('workspace-name')) ? $this->option('workspace-name') : null,
            storagePath: is_string($storage) ? $storage : null,
            dryRun: (bool) $this->option('dry-run'),
        );

        try {
            $report = $importer->run($options);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($options->dryRun ? 'Dry run: nothing was saved.' : "Imported into {$workspace}/{$blog}.");
        $this->table(
            ['Entity', 'Created', 'Already imported', 'Skipped'],
            collect($report->counts())->map(fn (array $count, string $entity) => [$entity, $count['created'], $count['existing'], $count['skipped']])->values()->all(),
        );

        foreach ($report->warnings() as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }
}
