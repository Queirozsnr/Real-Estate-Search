<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
#[ORM\Index(name: 'idx_property_city', columns: ['city'])]
#[ORM\Index(name: 'idx_property_price', columns: ['price'])]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @param list<string> $features
     * @param list<string> $images
     */
    public function __construct(
        #[ORM\Column(length: 160)]
        private string $title,

        #[ORM\Column(type: Types::TEXT)]
        private string $description,

        #[ORM\Column(length: 20, enumType: PropertyType::class)]
        private PropertyType $type,

        #[ORM\Column(length: 100)]
        private string $city,

        #[ORM\Column(length: 100)]
        private string $district,

        #[ORM\Column(length: 200)]
        private string $address,

        /** Purchase price in whole euros. */
        #[ORM\Column]
        private int $price,

        #[ORM\Column(type: Types::SMALLINT)]
        private int $bedrooms,

        #[ORM\Column(type: Types::SMALLINT)]
        private int $bathrooms,

        /** Living area in square metres. */
        #[ORM\Column]
        private int $livingArea,

        #[ORM\Column(type: Types::SMALLINT, nullable: true)]
        private ?int $yearBuilt,

        #[ORM\Column(type: Types::JSON)]
        private array $features,

        #[ORM\Column(type: Types::JSON)]
        private array $images,

        #[ORM\Column]
        private float $latitude,

        #[ORM\Column]
        private float $longitude,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $listedAt,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): PropertyType
    {
        return $this->type;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getDistrict(): string
    {
        return $this->district;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getBedrooms(): int
    {
        return $this->bedrooms;
    }

    public function getBathrooms(): int
    {
        return $this->bathrooms;
    }

    public function getLivingArea(): int
    {
        return $this->livingArea;
    }

    public function getYearBuilt(): ?int
    {
        return $this->yearBuilt;
    }

    /**
     * @return list<string>
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * @return list<string>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getListedAt(): \DateTimeImmutable
    {
        return $this->listedAt;
    }
}
