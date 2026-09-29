<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929012704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the property table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(160) NOT NULL, description CLOB NOT NULL, type VARCHAR(20) NOT NULL, city VARCHAR(100) NOT NULL, district VARCHAR(100) NOT NULL, address VARCHAR(200) NOT NULL, price INTEGER NOT NULL, bedrooms SMALLINT NOT NULL, bathrooms SMALLINT NOT NULL, living_area INTEGER NOT NULL, year_built SMALLINT DEFAULT NULL, features CLOB NOT NULL, images CLOB NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, listed_at DATE NOT NULL)');
        $this->addSql('CREATE INDEX idx_property_city ON property (city)');
        $this->addSql('CREATE INDEX idx_property_price ON property (price)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property');
    }
}
