<?php

namespace App\Migration\V1;

final readonly class ImportOptions
{
    public function __construct(
        public string $connection,
        public string $workspace,
        public string $blog,
        public string $language = 'pt-BR',
        public ?string $ownerEmail = null,
        public ?string $workspaceName = null,
        /** v1's storage/app/public folder, to import covers, the brand image and body images. */
        public ?string $storagePath = null,
        public bool $dryRun = false,
    ) {}
}
