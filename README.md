# ages/sklik-gateway

PHP klient pro **Seznam RS-API (Fénix)** — `https://api.sklik.cz/v1`.
Zaměřený na **Seznam Nákupy** a **statistické reporty** (útrata, prokliky, konverze, PNO…).

- REST/JSON, autentizace přes refresh token → access token (platnost ~1 h, klient ho cachuje a reusuje)
- Ošetřený rate limit (5 req/s → 429 s `Retry-After`, automatické opakování)
- PHP 8.4+, Guzzle, framework-agnostic (volitelná PSR-16 cache pro sdílení tokenu mezi requesty)

## Instalace

```bash
composer require ages/sklik-gateway
```

## Získání refresh tokenu

1. Přihlas se na [sklik.cz](https://www.sklik.cz/).
2. V sekci **Nástroje → API** vytvoř nový token a zvol scope (`r` pro čtení statistik).
3. Refresh token vlož do konfigurace (viz níže). Jeho platnost si volíš při vytvoření.

> Access token má platnost 1 hodinu. Klient ho získá jednou a reusuje — endpoint `/user/token` je rate-limited, nevolej ho před každým requestem.

## Použití

```php
use Ages\SklikGateway\Config\SklikConfig;
use Ages\SklikGateway\SklikGateway;
use Ages\SklikGateway\Nakupy\Request\ReportParams;
use Ages\SklikGateway\Enum\ReportFileFormat;
use Ages\SklikGateway\Enum\ReportStatisticsGranularity;

$config  = new SklikConfig(refreshToken: 'váš-refresh-token');
$gateway = new SklikGateway($config /*, $psr16cache */);

// 1) vytvoření agregovaného reportu (asynchronně vrátí id)
$params = new ReportParams(
    from: new DateTimeImmutable('-30 days'),
    to:   new DateTimeImmutable('now'),
    format: [ReportFileFormat::Json],
    granularity: ReportStatisticsGranularity::Daily,
);
$created = $gateway->nakupy()->createAggregatedReport($params);

// 2) stažení obsahu reportu, až je vygenerovaný
$report = $gateway->reports()->getReport($created->id);
echo $report->sums?->totalMoney;   // celková útrata
foreach ($report->stats as $row) { /* ... */ }
```

### Seznam Nákupy — shop items

```php
use Ages\SklikGateway\Nakupy\Request\ShopItemsQuery;
use Ages\SklikGateway\Nakupy\Request\ItemChange;

$list = $gateway->nakupy()->listShopItems(new ShopItemsQuery(limit: 100));
foreach ($list->items as $item) {
    echo $item->matchingId, ' ', $item->price, PHP_EOL;
}

// úprava max. 50 položek najednou (ITEM_ID z feedu)
$gateway->nakupy()->updateShopItems([
    new ItemChange(id: 'SKU-123', searchMaxCpc: 2.50, productMaxCpc: 3.00),
]);
```

### Detail produktu

```php
// jeden produkt
$product = $gateway->nakupy()->getProduct($productId);
echo $product?->productName, ' ', $product?->minPrice, '–', $product?->maxPrice;

// dávka (max 10 productId najednou)
$products = $gateway->nakupy()->getProducts([232, 233, 234]);
```

> **premiseId** je výchozí z konfigurace (`SklikConfig::$premiseId`); u každé metody
> ho lze přebít posledním argumentem (např. `getProduct($productId, $jinyPremiseId)`).
> Najdeš ho v rozhraní Sklik.cz – API pro jeho výpis neexistuje. **productId** je ID
> produktu v katalogu Zboží.cz/Seznam – z URL produktu, z `manufacturers/search`
> nebo z napárovaných shop-items.

### Další čtecí endpointy

```php
$gateway->nakupy()->listFeeds();
$gateway->nakupy()->listCampaigns();
$gateway->nakupy()->listShops($id);
$gateway->reports()->listReports(limit: 20);
```

## Integrace do Nette projektu (neon)

```neon
services:
    sklikConfig: Ages\SklikGateway\Config\SklikConfig(
        refreshToken: 'váš-refresh-token'
        premiseId: 123456           # výchozí provozovna pro /nakupy/* volání
        # userId: 123456            # volitelně – přístup k propojenému účtu
    )
    - Ages\SklikGateway\SklikGateway(@sklikConfig)
```

> Volitelný druhý argument je **PSR-16** cache (`Psr\SimpleCache\CacheInterface`).
> Když ji předáš, access token se sdílí mezi HTTP requesty; jinak se získává nový
> token per instance klienta. Nette `IStorage` není PSR-16 — pro sdílení tokenu
> zaregistruj adaptér (např. `symfony/cache` `Psr16Cache`) a předej ho sem.

## Rozšíření

Neobalené endpointy (např. `/sklik/reports/` list, diagnostika, recenze) jsou dostupné
přes nízkoúrovňový klient:

```php
$data = $gateway->client()->get('nakupy/diagnostics/item', ['premiseId' => $premiseId, 'itemId' => 'SKU-123']);
```

## Chyby

- `Ages\SklikGateway\Exception\SklikApiException` — nesprávný HTTP status (`->statusCode`, `->responseBody`)
- `Ages\SklikGateway\Exception\SklikException` — síťová / parsovací chyba (základní typ)
