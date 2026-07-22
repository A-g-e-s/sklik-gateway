<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Report\Response;

/**
 * Contents of a generated statistics report in JSON form (StreamedReportResponse).
 *
 * Individual stat rows depend on the requested split/granularity, so they are
 * exposed as raw associative arrays.
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
