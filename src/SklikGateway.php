<?php

declare(strict_types=1);

namespace Ages\SklikGateway;

use Ages\SklikGateway\Config\SklikConfig;
use Ages\SklikGateway\Http\SklikClient;
use Ages\SklikGateway\Nakupy\NakupyApi;
use Ages\SklikGateway\Report\ReportApi;
use GuzzleHttp\Client;
use Psr\SimpleCache\CacheInterface;

/**
 * Entry point for the Seznam RS-API (Fénix) gateway.
 *
 * Register this as a single service; it lazily builds the underlying HTTP client
 * and exposes the resource APIs (Seznam Nákupy, statistical reports).
 *
 * <code>
 * $gateway = new SklikGateway($config, $cache);
 * $report  = $gateway->nakupy()->createAggregatedReport($premiseId, $params);
 * $data    = $gateway->reports()->getReport($report->id);
 * </code>
 */
class SklikGateway
{
    private readonly SklikClient $client;

    private readonly ?int $premiseId;

    private ?NakupyApi $nakupy = null;

    private ?ReportApi $reports = null;

    public function __construct(
        SklikConfig $config,
        ?CacheInterface $cache = null,
        ?Client $httpClient = null,
    ) {
        $this->client = new SklikClient($config, $cache, $httpClient);
        $this->premiseId = $config->premiseId;
    }

    public function nakupy(): NakupyApi
    {
        return $this->nakupy ??= new NakupyApi($this->client, $this->premiseId);
    }

    public function reports(): ReportApi
    {
        return $this->reports ??= new ReportApi($this->client);
    }

    /**
     * Direct access to the low-level client for endpoints not yet wrapped.
     */
    public function client(): SklikClient
    {
        return $this->client;
    }
}
