<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Property;
use App\Enum\PropertyType;
use App\Search\PropertySearchCriteria;
use App\Search\PropertySearchResult;
use App\Search\SearchFacets;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function search(PropertySearchCriteria $criteria): PropertySearchResult
    {
        $qb = $this->createFilteredQueryBuilder($criteria);

        $total = (int) (clone $qb)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        [$field, $direction] = $criteria->sortOrder()->orderBy();

        /** @var list<Property> $items */
        $items = $qb
            ->orderBy('p.'.$field, $direction)
            ->addOrderBy('p.id', 'ASC') // deterministic order for stable pagination
            ->setFirstResult($criteria->offset())
            ->setMaxResults($criteria->perPage)
            ->getQuery()
            ->getResult();

        return new PropertySearchResult($items, $total, $criteria->page, $criteria->perPage);
    }

    public function facets(): SearchFacets
    {
        /** @var list<array{name: string, count: int|string}> $cities */
        $cities = $this->createQueryBuilder('p')
            ->select('p.city AS name', 'COUNT(p.id) AS count')
            ->groupBy('p.city')
            ->orderBy('p.city', 'ASC')
            ->getQuery()
            ->getArrayResult();

        /** @var list<array{type: PropertyType|string, count: int|string}> $typeRows */
        $typeRows = $this->createQueryBuilder('p')
            ->select('p.type AS type', 'COUNT(p.id) AS count')
            ->groupBy('p.type')
            ->getQuery()
            ->getArrayResult();

        $typeCounts = [];
        foreach ($typeRows as $row) {
            $type = $row['type'] instanceof PropertyType ? $row['type'] : PropertyType::from($row['type']);
            $typeCounts[$type->value] = (int) $row['count'];
        }

        /** @var array{minPrice: int|string|null, maxPrice: int|string|null, maxBedrooms: int|string|null} $ranges */
        $ranges = $this->createQueryBuilder('p')
            ->select('MIN(p.price) AS minPrice', 'MAX(p.price) AS maxPrice', 'MAX(p.bedrooms) AS maxBedrooms')
            ->getQuery()
            ->getSingleResult();

        return new SearchFacets(
            cities: array_map(
                static fn (array $row): array => ['name' => $row['name'], 'count' => (int) $row['count']],
                $cities,
            ),
            types: array_map(
                static fn (PropertyType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'count' => $typeCounts[$type->value] ?? 0,
                ],
                PropertyType::cases(),
            ),
            minPrice: (int) $ranges['minPrice'],
            maxPrice: (int) $ranges['maxPrice'],
            maxBedrooms: (int) $ranges['maxBedrooms'],
        );
    }

    private function createFilteredQueryBuilder(PropertySearchCriteria $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if (null !== $city = $criteria->normalizedCity()) {
            $qb->andWhere('LOWER(p.city) = :city')->setParameter('city', mb_strtolower($city));
        }

        if (null !== $criteria->minPrice) {
            $qb->andWhere('p.price >= :minPrice')->setParameter('minPrice', $criteria->minPrice);
        }

        if (null !== $criteria->maxPrice) {
            $qb->andWhere('p.price <= :maxPrice')->setParameter('maxPrice', $criteria->maxPrice);
        }

        if (null !== $criteria->minBedrooms) {
            $qb->andWhere('p.bedrooms >= :minBedrooms')->setParameter('minBedrooms', $criteria->minBedrooms);
        }

        if (null !== $type = $criteria->propertyType()) {
            $qb->andWhere('p.type = :type')->setParameter('type', $type->value);
        }

        return $qb;
    }
}
