<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Report\Response;

/**
 * A single report listing entry (GET /sklik/reports/).
 */
readonly class ReportItem
{
    /**
     * @param list<string>          $formats
     * @param array<string, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $reportType,
        public string $name,
        public string $status,
        public ?string $startDate,
        public ?string $endDate,
        public array $formats,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $formats = [];
        foreach ((array) ($data['formats'] ?? []) as $f) {
            if (is_string($f)) {
                $formats[] = $f;
            }
        }

        return new self(
            id: (int) ($data['id'] ?? 0),
            reportType: (string) ($data['reportType'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            startDate: isset($data['startDate']) ? (string) $data['startDate'] : null,
            endDate: isset($data['endDate']) ? (string) $data['endDate'] : null,
            formats: $formats,
            raw: $data,
        );
    }
}
