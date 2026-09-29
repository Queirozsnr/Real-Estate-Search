<?php

declare(strict_types=1);

namespace App\View;

use App\Entity\Property;

final readonly class PropertySummary
{
    public function __construct(
        public int $id,
        public string $title,
        public string $type,
        public string $city,
        public string $district,
        public int $price,
        public int $bedrooms,
        public int $bathrooms,
        public int $livingArea,
        public ?string $imageUrl,
        public string $listedAt,
    ) {
    }

    public static function fromEntity(Property $property): self
    {
        return new self(
            id: (int) $property->getId(),
            title: $property->getTitle(),
            type: $property->getType()->value,
            city: $property->getCity(),
            district: $property->getDistrict(),
            price: $property->getPrice(),
            bedrooms: $property->getBedrooms(),
            bathrooms: $property->getBathrooms(),
            livingArea: $property->getLivingArea(),
            imageUrl: $property->getImages()[0] ?? null,
            listedAt: $property->getListedAt()->format('Y-m-d'),
        );
    }
}
