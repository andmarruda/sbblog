<?php

namespace App\Console\Commands;

use App\Actions\Content\ArticleEditor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('articles:publish-scheduled')]
#[Description('Publish scheduled articles whose time has come')]
class PublishScheduledArticles extends Command
{
    public function handle(ArticleEditor $editor): int
    {
        $count = $editor->publishDue();

        $this->info("Published {$count} article(s).");

        return self::SUCCESS;
    }
}
