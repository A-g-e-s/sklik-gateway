<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Response;

/**
 * Product detail on Zboží.cz / Seznam Nákupy (ProductsResponse).
 *
 * The offer arrays (`shopItems`, `topShopItems`) are kept raw in {@see $raw} since
 * their shape depends on the request.
 */
readonly class Product
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $productId,
        public string $productName,
        public string $productUrl,
        public ?int $categoryId,
        /** Manufacturer ID – 64-bit unsigned number returned as string. */
        public ?string $manufacturerId,
        public ?float $minPrice,
        public ?float $maxPrice,
        /** Total number of shops offering this product. */
        public ?int $shopCount,
        public ?string $lastUpdate,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) ($data['productId'] ?? 0),
            productName: (string) ($data['productName'] ?? ''),
            productUrl: (string) ($data['productUrl'] ?? ''),
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            manufacturerId: isset($data['manufacturerId']) ? (string) $data['manufacturerId'] : null,
            minPrice: isset($data['minPrice']) ? (float) $data['minPrice'] : null,
            maxPrice: isset($data['maxPrice']) ? (float) $data['maxPrice'] : null,
            shopCount: isset($data['shopCount']) ? (int) $data['shopCount'] : null,
            lastUpdate: isset($data['lastUpdate']) ? (string) $data['lastUpdate'] : null,
            raw: $data,
        );
    }
}
