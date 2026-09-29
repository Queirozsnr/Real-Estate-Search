<?php

declare(strict_types=1);

namespace App\Import;

use App\Entity\Property;
use App\Enum\PropertyType;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PropertyImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $datasetPath,
    ) {
    }

    public function isEmpty(): bool
    {
        return 0 === $this->entityManager->getRepository(Property::class)->count();
    }

    public function purge(): void
    {
        $this->entityManager->createQuery('DELETE FROM '.Property::class)->execute();
    }

    /**
     * @return int Number of imported properties
     */
    public function import(?string $path = null): int
    {
        $path ??= $this->datasetPath;

        if (!is_file($path)) {
            throw new \RuntimeException(\sprintf('Dataset file "%s" does not exist.', $path));
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = json_decode((string) file_get_contents($path), true, flags: \JSON_THROW_ON_ERROR);

        foreach ($rows as $index => $row) {
            try {
                $this->entityManager->persist($this->createProperty($row));
            } catch (\Throwable $e) {
                throw new \RuntimeException(\sprintf('Invalid property at index %d: %s', $index, $e->getMessage()), previous: $e);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        return \count($rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createProperty(array $row): Property
    {
        return new Property(
            title: $row['title'],
            description: $row['description'],
            type: PropertyType::from($row['type']),
            city: $row['city'],
            district: $row['district'],
            address: $row['address'],
            price: $row['price'],
            bedrooms: $row['bedrooms'],
            bathrooms: $row['bathrooms'],
            livingArea: $row['livingArea'],
            yearBuilt: $row['yearBuilt'] ?? null,
            features: $row['features'] ?? [],
            images: $row['images'] ?? [],
            latitude: (float) $row['latitude'],
            longitude: (float) $row['longitude'],
            listedAt: new \DateTimeImmutable($row['listedAt']),
        );
    }
}
