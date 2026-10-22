<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Report\Response;

/**
 * Contents of a generated statistics report in JSON form (StreamedReportResponse).
 *
 * Individual stat rows are kept as raw associative arrays in `stats`; use
 * {@see rows()} for typed access.
 */
readonly class StreamedReport
{
    /**
     * @param list<array<string, mixed>> $stats
     */
    public function __construct(
        public array $stats,
        public ?ReportSums $sums,
    ) {
    }

    /**
     * Stat rows as typed objects.
     *
     * @return list<ReportRow>
     */
    public function rows(): array
    {
        return array_map(static fn (array $row): ReportRow => ReportRow::fromArray($row), $this->stats);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $stats = [];
        foreach ((array) ($data['stats'] ?? []) as $row) {
            if (is_array($row)) {
                $stats[] = $row;
            }
        }

        return new self(
            stats: $stats,
            sums: is_array($data['sums'] ?? null) ? ReportSums::fromArray($data['sums']) : null,
        );
    }
}
