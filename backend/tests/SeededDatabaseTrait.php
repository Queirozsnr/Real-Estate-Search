<?php

declare(strict_types=1);

namespace App\Tests;

use App\Import\PropertyImporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Recreates the test database schema and loads the real dataset (data/properties.json)
 * once per test class. The data is read-only, so tests can safely share it.
 */
trait SeededDatabaseTrait
{
    private static bool $databaseSeeded = false;

    protected static function seedDatabase(): void
    {
        if (self::$databaseSeeded) {
            return;
        }

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $container->get(PropertyImporter::class)->import();

        self::$databaseSeeded = true;
    }
}
