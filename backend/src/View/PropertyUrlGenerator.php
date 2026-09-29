<?php

declare(strict_types=1);

namespace App\View;

final readonly class PropertyUrlGenerator
{
    public function __construct(
        private string $frontendUrl,
    ) {
    }

    public function propertyUrl(int $id): string
    {
        return \sprintf('%s/properties/%d', rtrim($this->frontendUrl, '/'), $id);
    }
}
