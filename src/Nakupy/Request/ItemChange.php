<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Request;

/**
 * A single shop item patch (PATCH /nakupy/shop-items/).
 *
 * `id` is the ITEM_ID from your XML feed. Provide at least one attribute to change.
 */
readonly class ItemChange
{
    public function __construct(
        public string $id,
        /** Order processing time in days. */
        public ?int $availability = null,
        /** Price incl. VAT (PRICE_VAT in the feed). */
        public ?float $price = null,
        public ?float $priceBeforeDiscount = null,
        /** Max CPC on the product detail page. */
        public ?float $productMaxCpc = null,
        /** Max CPC for Seznam Nákupy search results. */
        public ?float $searchMaxCpc = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'availability' => $this->availability,
            'price' => $this->price,
            'priceBeforeDiscount' => $this->priceBeforeDiscount,
            'productMaxCpc' => $this->productMaxCpc,
            'searchMaxCpc' => $this->searchMaxCpc,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
