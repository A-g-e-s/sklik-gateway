<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy;

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
 * Statistics reports are generated asynchronously: the create* methods return a
 * report id; the actual data is downloaded through {@see \Ages\SklikGateway\Report\ReportApi}.
 */
class NakupyApi
{
    public function __construct(private readonly SklikClient $client)
    {
    }

    // -- Statistics ---------------------------------------------------------

    /** Aggregated shop statistics report (POST /nakupy/statistics/aggregated). */
    public function createAggregatedReport(int $premiseId, ReportParams $params): ReportCreated
    {
        return $this->createReport('nakupy/statistics/aggregated', $premiseId, $params);
    }

    /** Per-item statistics report (POST /nakupy/statistics/item). */
    public function createItemReport(int $premiseId, ReportParams $params): ReportCreated
    {
        return $this->createReport('nakupy/statistics/item', $premiseId, $params);
    }

    /** Item statistics grouped by categories (POST /nakupy/statistics/category). */
    public function createCategoryReport(int $premiseId, ReportParams $params): ReportCreated
    {
        return $this->createReport('nakupy/statistics/category', $premiseId, $params);
    }

    private function createReport(string $path, int $premiseId, ReportParams $params): ReportCreated
    {
        $data = $this->client->post($path, $params->toArray(), ['premiseId' => $premiseId]);

        return ReportCreated::fromArray($data);
    }

    // -- Shop items ---------------------------------------------------------

    /** List shop item attributes (GET /nakupy/shop-items/). */
    public function listShopItems(int $premiseId, ?ShopItemsQuery $query = null): ShopItemList
    {
        $params = ['premiseId' => $premiseId] + ($query?->toArray() ?? []);
        $data = $this->client->get('nakupy/shop-items/', $params);

        return ShopItemList::fromArray($data);
    }

    /**
     * Update shop item attributes (PATCH /nakupy/shop-items/). Max 50 items per call.
     *
     * @param list<ItemChange> $changes
     */
    public function updateShopItems(int $premiseId, array $changes): void
    {
        if ($changes === []) {
            throw new \InvalidArgumentException('At least one item change is required.');
        }
        if (count($changes) > 50) {
            throw new \InvalidArgumentException('At most 50 item changes per request are allowed.');
        }

        $payload = ['items' => array_map(static fn (ItemChange $c): array => $c->toArray(), $changes)];
        $this->client->patch('nakupy/shop-items/', $payload, ['premiseId' => $premiseId]);
    }

    // -- Products -----------------------------------------------------------

    /**
     * Retrieve product details (GET /nakupy/products/). Up to 10 product IDs per call.
     *
     * @param list<int> $productIds Seznam Nákupy product IDs (max 10).
     * @return list<Product>
     */
    public function getProducts(array $productIds, int $premiseId): array
    {
        if ($productIds === []) {
            throw new \InvalidArgumentException('At least one product ID is required.');
        }
        if (count($productIds) > 10) {
            throw new \InvalidArgumentException('At most 10 product IDs per request are allowed.');
        }

        $data = $this->client->get('nakupy/products/', [
            'productId' => $productIds,
            'premiseId' => $premiseId,
        ]);

        return array_map(
            static fn (array $item): Product => Product::fromArray($item),
            $this->items($data),
        );
    }

    /**
     * Convenience wrapper for a single product (GET /nakupy/products/).
     */
    public function getProduct(int $productId, int $premiseId): ?Product
    {
        return $this->getProducts([$productId], $premiseId)[0] ?? null;
    }

    // -- Catalog ------------------------------------------------------------

    /**
     * List feeds (GET /nakupy/feeds/).
     *
     * @return list<Feed>
     */
    public function listFeeds(int $premiseId): array
    {
        $data = $this->client->get('nakupy/feeds/', ['premiseId' => $premiseId]);

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
    public function listCampaigns(int $premiseId): array
    {
        $data = $this->client->get('nakupy/campaigns/', ['premiseId' => $premiseId]);

        return array_map(
            static fn (array $item): Campaign => Campaign::fromArray($item),
            $this->items($data),
        );
    }

    /**
     * List shop details (GET /nakupy/shops/).
     *
     * @return list<Shop>
     */
    public function listShops(int $id, int $premiseId): array
    {
        $data = $this->client->get('nakupy/shops/', ['id' => $id, 'premiseId' => $premiseId]);

        return array_map(
            static fn (array $item): Shop => Shop::fromArray($item),
            $this->items($data),
        );
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
