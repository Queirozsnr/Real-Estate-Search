<?php

declare(strict_types=1);

namespace App\Search;

final class PropertyNotFoundException extends \RuntimeException
{
    public static function withId(int $id): self
    {
        return new self(\sprintf('Property with id %d was not found.', $id));
    }
}
