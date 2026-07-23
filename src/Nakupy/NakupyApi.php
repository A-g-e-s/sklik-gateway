<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy;

use Ages\SklikGateway\Exception\SklikException;
use Ages\SklikGateway\Http\SklikClient;
use Ages\SklikGateway\Nakupy\Request\ItemChange;
use Ages\SklikGateway\Nakupy\Request\ReportParams;
use Ages\SklikGateway\Nakupy\Request\ShopItemsQuery;
use Ages\SklikGateway\Nakupy\Response\Campaign;
use Ages\SklikGateway\Nakupy\Response\Feed;
use Ages\SklikGateway\Nakupy\Response\Product;
use Ages\SklikGateway\Nakupy\Response\ReportCreated;
use Ages\SklikGateway\Nakupy\Response\Shop;
use Ages\SklikGateway\Nakupy\Response\ShopItemList;

/**
 * Seznam Nákupy endpoints (/nakupy/*).
 *
 * Every call needs a premise (shop) ID. It can be passed explicitly, otherwise
 * the default from {@see \Ages\SklikGateway\Config\SklikConfig::$premiseId} is used.
 *
 * Statistics reports are generated asynchronously: the create* methods return a
 * report id; the actual data is downloaded through {@see \Ages\SklikGateway\Report\ReportApi}.
 */
class NakupyApi
{
    public function __construct(
        private readonly SklikClient $client,
        private readonly ?int $defaultPremiseId = null,
    ) {
    }

    // -- Statistics ---------------------------------------------------------

    /** Aggregated shop statistics report (POST /nakupy/statistics/aggregated). */
    public function createAggregatedReport(ReportParams $params, ?int $premiseId = null): ReportCreated
    {
        return $this->createReport('nakupy/statistics/aggregated', $params, $premiseId);
    }

    /** Per-item statistics report (POST /nakupy/statistics/item). */
    public function createItemReport(ReportParams $params, ?int $premiseId = null): ReportCreated
    {
        return $this->createReport('nakupy/statistics/item', $params, $premiseId);
    }

    /** Item statistics grouped by categories (POST /nakupy/statistics/category). */
    public function createCategoryReport(ReportParams $params, ?int $premiseId = null): ReportCreated
    {
        return $this->createReport('nakupy/statistics/category', $params, $premiseId);
    }

    private function createReport(string $path, ReportParams $params, ?int $premiseId): ReportCreated
    {
        $data = $this->client->post($path, $params->toArray(), ['premiseId' => $this->premiseId($premiseId)]);

        return ReportCreated::fromArray($data);
    }

    // -- Shop items ---------------------------------------------------------

    /** List shop item attributes (GET /nakupy/shop-items/). */
    public function listShopItems(?ShopItemsQuery $query = null, ?int $premiseId = null): ShopItemList
    {
        $params = ['premiseId' => $this->premiseId($premiseId)] + ($query?->toArray() ?? []);
        $data = $this->client->get('nakupy/shop-items/', $params);

        return ShopItemList::fromArray($data);
    }

    /**
     * Update shop item attributes (PATCH /nakupy/shop-items/). Max 50 items per call.
     *
     * @param list<ItemChange> $changes
     */
    public function updateShopItems(array $changes, ?int $premiseId = null): void
    {
        if ($changes === []) {
            throw new \InvalidArgumentException('At least one item change is required.');
        }
        if (count($changes) > 50) {
            throw new \InvalidArgumentException('At most 50 item changes per request are allowed.');
        }

        $payload = ['items' => array_map(static fn (ItemChange $c): array => $c->toArray(), $changes)];
        $this->client->patch('nakupy/shop-items/', $payload, ['premiseId' => $this->premiseId($premiseId)]);
    }

    // -- Products -----------------------------------------------------------

    /**
     * Retrieve product details (GET /nakupy/products/). Up to 10 product IDs per call.
     *
     * @param list<int> $productIds Seznam Nákupy product IDs (max 10).
     * @return list<Product>
     */
    public function getProducts(array $productIds, ?int $premiseId = null): array
    {
        if ($productIds === []) {
            throw new \InvalidArgumentException('At least one product ID is required.');
        }
        if (count($productIds) > 10) {
            throw new \InvalidArgumentException('At most 10 product IDs per request are allowed.');
        }

        $data = $this->client->get('nakupy/products/', [
            'productId' => $productIds,
            'premiseId' => $this->premiseId($premiseId),
        ]);

        return array_map(
            static fn (array $item): Product => Product::fromArray($item),
            $this->items($data),
        );
    }

    /**
     * Convenience wrapper for a single product (GET /nakupy/products/).
     */
    public function getProduct(int $productId, ?int $premiseId = null): ?Product
    {
        return $this->getProducts([$productId], $premiseId)[0] ?? null;
    }

    // -- Catalog ------------------------------------------------------------

    /**
     * List feeds (GET /nakupy/feeds/).
     *
     * @return list<Feed>
     */
    public function listFeeds(?int $premiseId = null): array
    {
        $data = $this->client->get('nakupy/feeds/', ['premiseId' => $this->premiseId($premiseId)]);

        return array_map(
            static fn (array $item): Feed => Feed::fromArray($item),
            $this->items($data),
        );
    }

    /**
     * List campaigns (GET /nakupy/campaigns/).
     *
     * @return list<Campaign>
     */
    public function listCampaigns(?int $premiseId = null): array
    {
        $data = $this->client->get('nakupy/campaigns/', ['premiseId' => $this->premiseId($premiseId)]);

        return array_map(
            static fn (array $item): Campaign => Campaign::fromArray($item),
            $this->items($data),
        );
    }

    /**
     * List shop details (GET /nakupy/shops/). Up to 100 shop IDs per call.
     *
     * @param list<int> $ids Shop (premise) IDs to look up.
     * @return list<Shop>
     */
    public function listShops(array $ids, ?int $premiseId = null): array
    {
        if ($ids === []) {
            throw new \InvalidArgumentException('At least one shop ID is required.');
        }
        if (count($ids) > 100) {
            throw new \InvalidArgumentException('At most 100 shop IDs per request are allowed.');
        }

        $data = $this->client->get('nakupy/shops/', [
            'id' => $ids,
            'premiseId' => $this->premiseId($premiseId),
        ]);

        return array_map(
            static fn (array $item): Shop => Shop::fromArray($item),
            $this->items($data),
        );
    }

    /**
     * Resolve the shops that offer a given product, keyed by premise (shop) ID.
     *
     * Combines the product's `shopItems` (which carry the offering shops' premise IDs)
     * with a {@see listShops()} lookup to get the shop names/ratings.
     *
     * @return array<int, Shop>
     */
    public function getProductShops(Product $product, ?int $premiseId = null): array
    {
        $ids = [];
        foreach ((array) ($product->raw['shopItems'] ?? []) as $item) {
            if (is_array($item) && isset($item['premiseId'])) {
                $id = (int) $item['premiseId'];
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $shops = [];
        foreach (array_chunk(array_values($ids), 100) as $chunk) {
            foreach ($this->listShops($chunk, $premiseId) as $shop) {
                $shops[$shop->premiseId] = $shop;
            }
        }

        return $shops;
    }

    /**
     * Resolves the premise ID for a call: the explicit one wins, otherwise the
     * configured default. Throws when neither is available.
     */
    private function premiseId(?int $premiseId): int
    {
        $resolved = $premiseId ?? $this->defaultPremiseId;
        if ($resolved === null) {
            throw new SklikException(
                'No premise ID provided and no default configured in SklikConfig::$premiseId.',
            );
        }

        return $resolved;
    }

    /**
     * Extracts the `items` list from a ListResponse envelope.
     *
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    private function items(array $data): array
    {
        $out = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
