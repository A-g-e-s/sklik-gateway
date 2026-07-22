<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Request;

/**
 * Query filters for listing shop items (GET /nakupy/shop-items/).
 *
 * `premiseId` is passed separately to the API call. All fields here are optional
 * filters; unset ones are omitted from the query string.
 */
readonly class ShopItemsQuery
{
    /**
     * @param list<string>|null $itemId Restrict to specific ITEM_IDs from your feed.
     */
    public function __construct(
        public ?bool $paired = null,
        public ?int $productCategoryId = null,
        public ?int $productSegmentId = null,
        public ?int $shopItemCategoryId = null,
        public ?int $shopItemSegmentId = null,
        public ?int $shopItemManufacturerId = null,
        public ?int $productManufacturerId = null,
        public ?int $limit = null,
        public ?int $offset = null,
        public ?array $itemId = null,
        public ?bool $loadProductDetail = null,
        public ?bool $loadSearchInfo = null,
        public ?string $includedCondition = null,
        public ?string $excludedCondition = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'paired' => $this->paired,
            'productCategoryId' => $this->productCategoryId,
            'productSegmentId' => $this->productSegmentId,
            'shopItemCategoryId' => $this->shopItemCategoryId,
            'shopItemSegmentId' => $this->shopItemSegmentId,
            'shopItemManufacturerId' => $this->shopItemManufacturerId,
            'productManufacturerId' => $this->productManufacturerId,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'itemId' => $this->itemId,
            'loadProductDetail' => $this->loadProductDetail,
            'loadSearchInfo' => $this->loadSearchInfo,
            'includedCondition' => $this->includedCondition,
            'excludedCondition' => $this->excludedCondition,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
