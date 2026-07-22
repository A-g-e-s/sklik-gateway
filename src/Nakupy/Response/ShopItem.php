<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Response;

/**
 * A single shop item as returned by GET /nakupy/shop-items/.
 *
 * The full raw payload is kept in {@see $raw} since the API exposes many optional
 * fields (product detail, search info, ...) depending on the query flags.
 */
readonly class ShopItem
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $matchingId,
        public int $premiseId,
        public float $price,
        public ?int $availability,
        public ?float $minCpc,
        public ?float $minPriceDelivery,
        public ?float $minPricePickup,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            matchingId: (string) ($data['matchingId'] ?? ''),
            premiseId: (int) ($data['premiseId'] ?? 0),
            price: (float) ($data['price'] ?? 0),
            availability: isset($data['availability']) ? (int) $data['availability'] : null,
            minCpc: isset($data['minCpc']) ? (float) $data['minCpc'] : null,
            minPriceDelivery: isset($data['minPriceDelivery']) ? (float) $data['minPriceDelivery'] : null,
            minPricePickup: isset($data['minPricePickup']) ? (float) $data['minPricePickup'] : null,
            raw: $data,
        );
    }
}
