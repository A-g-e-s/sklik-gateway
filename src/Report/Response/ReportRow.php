<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Report\Response;

use DateTimeImmutable;

/**
 * A single statistics row of a report (ReportData).
 *
 * `date` is filled only for daily granularity; split/item fields only when the
 * report was requested with the matching split or as an item report.
 */
readonly class ReportRow
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public ?DateTimeImmutable $date,
        public int $impressions,
        public int $clicks,
        public float $totalMoney,
        public float $avgCpc,
        public float $ctr,
        public int $conversions,
        public int $directConversions,
        public float $conversionRatio,
        public float $conversionPrice,
        public float $conversionValue,
        public float $pno,
        public int $itemsSold,
        public ?string $premiseName = null,
        public ?string $itemId = null,
        public ?string $feedItem = null,
        public ?string $offerCategory = null,
        public ?string $offerType = null,
        public ?string $webType = null,
        public ?string $deviceType = null,
        public ?string $productType = null,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: self::parseDate($data['date'] ?? null),
            impressions: (int) ($data['impressions'] ?? 0),
            clicks: (int) ($data['clicks'] ?? 0),
            totalMoney: (float) ($data['totalMoney'] ?? 0),
            avgCpc: (float) ($data['avgCpc'] ?? 0),
            ctr: (float) ($data['ctr'] ?? 0),
            conversions: (int) ($data['conversions'] ?? 0),
            directConversions: (int) ($data['directConversions'] ?? 0),
            conversionRatio: (float) ($data['conversionRatio'] ?? 0),
            conversionPrice: (float) ($data['conversionPrice'] ?? 0),
            conversionValue: (float) ($data['conversionValue'] ?? 0),
            pno: (float) ($data['pno'] ?? 0),
            itemsSold: (int) ($data['itemsSold'] ?? 0),
            premiseName: self::stringOrNull($data['plaPremiseName'] ?? null),
            itemId: self::stringOrNull($data['itemId'] ?? null),
            feedItem: self::stringOrNull($data['feedItem'] ?? null),
            offerCategory: self::stringOrNull($data['offerCategory'] ?? null),
            offerType: self::stringOrNull($data['offerType'] ?? null),
            webType: self::stringOrNull($data['webType'] ?? null),
            deviceType: self::stringOrNull($data['deviceType'] ?? null),
            productType: self::stringOrNull($data['productType'] ?? null),
            raw: $data,
        );
    }

    /**
     * Sums rows into one row for the given period start. Absolute metrics are
     * added up, ratios (CTR, CPC, PNO…) are recomputed from the totals.
     *
     * @param list<self> $rows
     */
    public static function sum(?DateTimeImmutable $date, array $rows): self
    {
        $impressions = $clicks = $conversions = $directConversions = $itemsSold = 0;
        $totalMoney = $conversionValue = 0.0;
        foreach ($rows as $row) {
            $impressions += $row->impressions;
            $clicks += $row->clicks;
            $conversions += $row->conversions;
            $directConversions += $row->directConversions;
            $itemsSold += $row->itemsSold;
            $totalMoney += $row->totalMoney;
            $conversionValue += $row->conversionValue;
        }

        return new self(
            date: $date,
            impressions: $impressions,
            clicks: $clicks,
            totalMoney: round($totalMoney, 2),
            avgCpc: $clicks > 0 ? round($totalMoney / $clicks, 2) : 0.0,
            ctr: $impressions > 0 ? round($clicks / $impressions, 4) : 0.0,
            conversions: $conversions,
            directConversions: $directConversions,
            conversionRatio: $clicks > 0 ? round($conversions / $clicks, 4) : 0.0,
            conversionPrice: $conversions > 0 ? round($totalMoney / $conversions, 2) : 0.0,
            conversionValue: round($conversionValue, 2),
            pno: $conversionValue > 0 ? round($totalMoney / $conversionValue, 4) : 0.0,
            itemsSold: $itemsSold,
            premiseName: $rows[0]->premiseName ?? null,
        );
    }

    /**
     * The API returns dates as `d.m.Y`; ISO dates are accepted as a fallback.
     */
    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $value);
        if ($date !== false) {
            return $date;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
