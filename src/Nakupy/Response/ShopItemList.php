<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Response;

/**
 * Paginated list of shop items (ListShopItemsResponse).
 */
readonly class ShopItemList
{
    /**
     * @param list<ShopItem> $items
     */
    public function __construct(
        public array $items,
        public ?int $offset,
        public ?int $totalCount,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (is_array($item)) {
                $items[] = ShopItem::fromArray($item);
            }
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        return new self(
            items: $items,
            offset: isset($meta['offset']) ? (int) $meta['offset'] : null,
            totalCount: isset($meta['totalCount']) ? (int) $meta['totalCount'] : null,
        );
    }
}
