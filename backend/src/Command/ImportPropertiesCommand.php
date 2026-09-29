<?php

declare(strict_types=1);

namespace App\Command;

use App\Import\PropertyImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:properties:import', description: 'Imports the property dataset (data/properties.json) into the database')]
final readonly class ImportPropertiesCommand
{
    public function __construct(
        private PropertyImporter $importer,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Delete existing properties before importing')]
        bool $purge = false,
        #[Option(description: 'Only import when the database contains no properties (idempotent, used on container start)')]
        bool $ifEmpty = false,
        #[Option(description: 'Path to an alternative JSON dataset')]
        ?string $file = null,
    ): int {
        if ($ifEmpty && !$this->importer->isEmpty()) {
            $io->note('Properties already imported, skipping.');

            return Command::SUCCESS;
        }

        if ($purge) {
            $this->importer->purge();
        }

        $count = $this->importer->import($file);
        $io->success(\sprintf('Imported %d properties.', $count));

        return Command::SUCCESS;
    }
}
