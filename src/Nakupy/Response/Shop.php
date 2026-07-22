<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Response;

/**
 * A shop's details (GET /nakupy/shops/).
 */
readonly class Shop
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $name,
        public int $premiseId,
        public ?float $rating,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            premiseId: (int) ($data['premiseId'] ?? 0),
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            raw: $data,
        );
    }
}
