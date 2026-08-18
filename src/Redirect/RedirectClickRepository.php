<?php

declare(strict_types=1);

namespace App\Redirect;

use App\Infrastructure\Persistence\RedirectClick\RedirectClick;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of RedirectClick
 * @extends Select\Repository<TEntity>
 */
final class RedirectClickRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @throws Throwable
     */
    public function save(RedirectClick $click): void
    {
        $this->entityWriter->write([$click]);
    }

    /**
     * Counts, per country, how many clicks a given tracked link has ever
     * recorded — the data the choropleth map in `redirect/map.php` colors
     * countries by. Clicks with no resolved country (a failed/skipped
     * geo-IP lookup) are excluded, since the map has nowhere meaningful
     * to draw them. Aggregated in PHP rather than a SQL GROUP BY — this
     * table's volume (personal-project link clicks, not high-traffic
     * analytics) makes that entirely adequate, and avoids relying on an
     * unconfirmed shape for Cycle's own raw query-builder aggregate API.
     *
     * @return array<string, int> country_code (lowercase ISO 3166-1
     *     alpha-2) => click count
     */
    public function countsByCountryQuery(string $linkKey): array
    {
        /** @var list<RedirectClick> $clicks */
        $clicks = $this->select()
            ->where(['link_key' => $linkKey])
            ->fetchAll();

        $counts = [];
        foreach ($clicks as $click) {
            $countryCode = $click->getCountryCode();
            if ($countryCode === null) {
                continue;
            }
            $counts[$countryCode] = ($counts[$countryCode] ?? 0) + 1;
        }

        return $counts;
    }
}
