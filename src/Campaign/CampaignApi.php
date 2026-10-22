<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Campaign;

use Ages\SklikGateway\Campaign\Response\CampaignStats;
use Ages\SklikGateway\Enum\CampaignType;
use Ages\SklikGateway\Enum\ReportStatisticsGranularity;
use Ages\SklikGateway\Http\SklikClient;
use DateTimeInterface;

/**
 * Sklik campaign endpoints (/sklik/campaigns/).
 *
 * Unlike the Nákupy statistics reports, campaign statistics are returned
 * immediately and cover every campaign type (search, content, Zboží…).
 */
class CampaignApi
{
    private const int PageLimit = 1000;

    /** Requested campaign + statistic attributes. */
    private const array Attributes = ['id', 'name', 'type', 'status', 'totalMoney', 'clicks', 'impressions', 'conversions', 'conversionValue'];

    public function __construct(private readonly SklikClient $client)
    {
    }

    /**
     * Statistics per campaign for a period (both days inclusive).
     *
     * With granularity `None` there is one row per campaign; otherwise one row per
     * campaign and time bucket (`date` = start of the bucket). The API allows at most
     * 60 buckets: daily up to a month, weekly up to a year, monthly+ up to 60 periods.
     *
     * @param list<CampaignType>|null $types       Restrict to campaign types.
     * @param bool                    $withoutZero Skip campaigns (buckets) without any statistics.
     * @return list<CampaignStats>
     */
    public function getCampaignStats(
        DateTimeInterface $from,
        DateTimeInterface $to,
        ReportStatisticsGranularity $granularity = ReportStatisticsGranularity::None,
        ?array $types = null,
        bool $withoutZero = true,
    ): array {
        if ($from > $to) {
            throw new \InvalidArgumentException('Statistics period start is after its end.');
        }

        $query = [
            'statisticsDateFrom' => $from->format('Y-m-d'),
            'statisticsDateTo' => $to->format('Y-m-d'),
            // the campaign endpoint calls the whole-period aggregate `total`
            'statisticsGranularity' => $granularity === ReportStatisticsGranularity::None ? 'total' : $granularity->value,
            'showEntityWithZeroStatistic' => !$withoutZero,
            'a' => self::Attributes,
            'type' => $types !== null ? array_map(static fn (CampaignType $t): string => $t->value, $types) : null,
            'limit' => self::PageLimit,
        ];

        $out = [];
        for ($offset = 0; ; $offset += self::PageLimit) {
            $items = (array) ($this->client->get('sklik/campaigns/', $query + ['offset' => $offset])['items'] ?? []);
            foreach ($items as $item) {
                if (is_array($item)) {
                    $out[] = CampaignStats::fromArray($item);
                }
            }
            if (count($items) < self::PageLimit) {
                break;
            }
        }

        return $out;
    }
}
