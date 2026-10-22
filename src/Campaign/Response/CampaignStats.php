<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Campaign\Response;

use Ages\SklikGateway\Enum\CampaignType;
use DateTimeImmutable;

/**
 * Statistics of one campaign for a period (or one time bucket of it).
 *
 * The API returns money in hellers; values here are converted to CZK.
 */
readonly class CampaignStats
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        /** Null for a type not known to this client – see {@see $typeRaw}. */
        public ?CampaignType $type,
        public string $typeRaw,
        public string $status,
        /** Start of the time bucket; null for granularity `total`. */
        public ?DateTimeImmutable $date,
        /** Spend (clicks + impressions) in CZK. */
        public float $totalMoney,
        public int $clicks,
        public int $impressions,
        public int $conversions,
        /** Value of conversions in CZK. */
        public float $conversionValue,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $type = is_string($data['type'] ?? null) ? $data['type'] : '';
        $date = is_string($data['firstDate'] ?? null) ? DateTimeImmutable::createFromFormat('!Y-m-d', substr($data['firstDate'], 0, 10)) : false;

        return new self(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : 0,
            name: is_string($data['name'] ?? null) ? $data['name'] : '',
            type: CampaignType::tryFrom($type),
            typeRaw: $type,
            status: is_string($data['status'] ?? null) ? $data['status'] : '',
            date: $date !== false ? $date : null,
            totalMoney: self::hellers($data['totalMoney'] ?? null),
            clicks: is_numeric($data['clicks'] ?? null) ? (int) $data['clicks'] : 0,
            impressions: is_numeric($data['impressions'] ?? null) ? (int) $data['impressions'] : 0,
            conversions: is_numeric($data['conversions'] ?? null) ? (int) $data['conversions'] : 0,
            conversionValue: self::hellers($data['conversionValue'] ?? null),
            raw: $data,
        );
    }

    /** Average cost per click in CZK. */
    public function avgCpc(): float
    {
        return $this->clicks > 0 ? round($this->totalMoney / $this->clicks, 2) : 0.0;
    }

    private static function hellers(mixed $value): float
    {
        return is_numeric($value) ? round((float) $value / 100, 2) : 0.0;
    }
}
