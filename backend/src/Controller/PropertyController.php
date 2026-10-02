<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\PropertyType;
use App\Search\PropertyCatalog;
use App\Search\PropertySearchCriteria;
use App\View\CityMarketOverview;
use App\View\PropertyDetails;
use App\View\PropertySummary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/properties', name: 'api_properties_', format: 'json')]
final class PropertyController extends AbstractController
{
    public function __construct(
        private readonly PropertyCatalog $catalog,
    ) {
    }

    #[Route('', name: 'search', methods: ['GET'])]
    public function search(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PropertySearchCriteria $criteria = new PropertySearchCriteria(),
    ): JsonResponse {
        $result = $this->catalog->search($criteria);

        return $this->respond([
            'data' => array_map(PropertySummary::fromEntity(...), $result->items),
            'meta' => [
                'total' => $result->total,
                'page' => $result->page,
                'perPage' => $result->perPage,
                'totalPages' => $result->totalPages(),
                'filters' => (object) $criteria->activeFilters(),
                'sort' => $criteria->sort,
            ],
        ]);
    }

    #[Route('/facets', name: 'facets', methods: ['GET'])]
    public function facets(): JsonResponse
    {
        return $this->respond($this->catalog->facets());
    }

    /**
     * Price statistics per city, optionally for one property type.
     */
    #[Route('/market', name: 'market', methods: ['GET'])]
    public function market(#[MapQueryParameter] ?string $type = null): JsonResponse
    {
        $type = null === $type ? null : (PropertyType::tryFrom($type) ?? throw new UnprocessableEntityHttpException(\sprintf(
            'Unknown property type "%s". Allowed values: "%s".',
            $type,
            implode('", "', PropertyType::values()),
        )));

        return $this->respond([
            'data' => array_map(CityMarketOverview::fromStats(...), $this->catalog->marketOverview($type)),
            'meta' => ['type' => $type?->value],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $property = $this->catalog->get($id);

        return $this->respond(['data' => PropertyDetails::fromEntity($property, $this->catalog->marketComparison($property))]);
    }

    private function respond(mixed $data): JsonResponse
    {
        // Keep URLs and umlauts readable in the JSON output.
        return $this->json($data, context: [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
        ]);
    }
}
