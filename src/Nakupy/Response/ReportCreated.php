<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Response;

/**
 * Result of creating a statistics report request.
 *
 * The generated report is downloaded afterwards via ReportApi::getReport() using this id.
 */
readonly class ReportCreated
{
    /**
     * @param array<string, mixed> $meta Echoed request params.
     */
    public function __construct(
        public int $id,
        public array $meta = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            meta: is_array($data['meta'] ?? null) ? $data['meta'] : [],
        );
    }
}
