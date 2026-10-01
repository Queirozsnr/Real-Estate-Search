<?php

declare(strict_types=1);

namespace App\View;

use App\Entity\Property;

final readonly class PropertyDetails
{
    /**
     * @param list<string>                              $features
     * @param list<string>                              $images
     * @param array{latitude: float, longitude: float} $location
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $type,
        public string $city,
        public string $district,
        public string $address,
        public int $price,
        public int $pricePerSquareMetre,
        public int $bedrooms,
        public int $bathrooms,
        public int $livingArea,
        public ?int $yearBuilt,
        public array $features,
        public array $images,
        public array $location,
        public string $listedAt,
    ) {
    }

    public static function fromEntity(Property $property): self
    {
        return new self(
            id: (int) $property->getId(),
            title: $property->getTitle(),
            description: $property->getDescription(),
            type: $property->getType()->value,
            city: $property->getCity(),
            district: $property->getDistrict(),
            address: $property->getAddress(),
            price: $property->getPrice(),
            pricePerSquareMetre: $property->getPricePerSquareMetre(),
            bedrooms: $property->getBedrooms(),
            bathrooms: $property->getBathrooms(),
            livingArea: $property->getLivingArea(),
            yearBuilt: $property->getYearBuilt(),
            features: $property->getFeatures(),
            images: $property->getImages(),
            location: ['latitude' => $property->getLatitude(), 'longitude' => $property->getLongitude()],
            listedAt: $property->getListedAt()->format('Y-m-d'),
        );
    }
}
