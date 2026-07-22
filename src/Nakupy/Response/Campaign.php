<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Response;

/**
 * A Seznam Nákupy campaign (GET /nakupy/campaigns/).
 *
 * Nested structures (budget, websites, devices, products, ...) are kept in {@see $raw}.
 */
readonly class Campaign
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public int $premiseId,
        public string $status,
        public bool $deleted,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            premiseId: (int) ($data['premiseId'] ?? 0),
            status: (string) ($data['status'] ?? ''),
            deleted: (bool) ($data['deleted'] ?? false),
            raw: $data,
        );
    }
}
