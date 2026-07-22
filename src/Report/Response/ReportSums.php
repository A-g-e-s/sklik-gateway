<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Report\Response;

/**
 * Aggregated totals of a statistics report (ReportDataSum).
 */
readonly class ReportSums
{
    public function __construct(
        public int $impressions,
        public int $clicks,
        public int $conversions,
        public float $conversionPrice,
        public float $conversionRatio,
        public float $conversionValue,
        public int $directConversions,
        public float $avgCpc,
        public float $ctr,
        public float $pno,
        public float $totalMoney,
        public int $itemsSold,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            impressions: (int) ($data['impressions'] ?? 0),
            clicks: (int) ($data['clicks'] ?? 0),
            conversions: (int) ($data['conversions'] ?? 0),
            conversionPrice: (float) ($data['conversionPrice'] ?? 0),
            conversionRatio: (float) ($data['conversionRatio'] ?? 0),
            conversionValue: (float) ($data['conversionValue'] ?? 0),
            directConversions: (int) ($data['directConversions'] ?? 0),
            avgCpc: (float) ($data['avgCpc'] ?? 0),
            ctr: (float) ($data['ctr'] ?? 0),
            pno: (float) ($data['pno'] ?? 0),
            totalMoney: (float) ($data['totalMoney'] ?? 0),
            itemsSold: (int) ($data['itemsSold'] ?? 0),
        );
    }
}
