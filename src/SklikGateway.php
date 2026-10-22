<?php

declare(strict_types=1);

namespace Ages\SklikGateway;

use Ages\SklikGateway\Config\SklikConfig;
use Ages\SklikGateway\Enum\ReportStatisticsGranularity;
use Ages\SklikGateway\Exception\SklikException;
use Ages\SklikGateway\Http\SklikClient;
use Ages\SklikGateway\Nakupy\NakupyApi;
use Ages\SklikGateway\Nakupy\Request\ReportParams;
use Ages\SklikGateway\Report\ReportApi;
use Ages\SklikGateway\Report\Response\ReportRow;
use Ages\SklikGateway\Report\Response\ReportSums;
use Ages\SklikGateway\User\Response\Credit;
use DateTimeImmutable;
use DateTimeInterface;
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
     * Current account credit (GET /user/me/credit), shared by Sklik and Seznam Nákupy.
     */
    public function getCredit(): Credit
    {
        return Credit::fromArray($this->client->get('user/me/credit'));
    }

    /**
     * Seznam Nákupy costs and traffic for a period, aggregated across the whole premise.
     *
     * Creates an aggregated statistics report, waits until it is generated and returns
     * its totals: `totalMoney` (spend), `impressions`, `clicks`, `avgCpc`, `ctr`, conversions…
     * Blocking call – use from cron/CLI rather than in a web request.
     *
     * @param int $timeout Max seconds to wait for the report.
     * @throws SklikException When the report fails or contains no totals.
     */
    public function getCosts(
        DateTimeInterface $from,
        DateTimeInterface $to,
        ?int $premiseId = null,
        int $timeout = 60,
    ): ReportSums {
        $created = $this->nakupy()->createAggregatedReport(new ReportParams(from: $from, to: $to), $premiseId);
        $report = $this->reports()->waitForReport($created->id, $timeout);

        return $report->sums ?? throw new SklikException("Sklik report {$created->id} contains no totals.");
    }

    /**
     * Seznam Nákupy costs and traffic for a period, broken down by day/week/month/quarter/year.
     *
     * The API only honours daily granularity (weekly and coarser return a single
     * undated row), so daily rows are fetched and grouped here. Each returned row
     * is dated by the start of its period (Monday, 1st of month…); ratios are
     * recomputed from the summed totals. Periods without data are omitted.
     *
     * @param int $timeout Max seconds to wait for the report.
     * @return list<ReportRow> Ordered by date.
     * @throws SklikException When the report fails.
     */
    public function getCostsBreakdown(
        DateTimeInterface $from,
        DateTimeInterface $to,
        ReportStatisticsGranularity $granularity = ReportStatisticsGranularity::Daily,
        ?int $premiseId = null,
        int $timeout = 60,
    ): array {
        if ($granularity === ReportStatisticsGranularity::None) {
            throw new \InvalidArgumentException('Use getCosts() for totals without a breakdown.');
        }

        $params = new ReportParams(from: $from, to: $to, granularity: ReportStatisticsGranularity::Daily);
        $created = $this->nakupy()->createAggregatedReport($params, $premiseId);
        $rows = $this->reports()->waitForReport($created->id, $timeout)->rows();

        /** @var array<string, list<ReportRow>> $groups */
        $groups = [];
        foreach ($rows as $row) {
            if ($row->date === null) {
                continue;
            }
            $groups[self::periodStart($row->date, $granularity)->format('Y-m-d')][] = $row;
        }
        ksort($groups);

        $out = [];
        foreach ($groups as $start => $group) {
            $out[] = $granularity === ReportStatisticsGranularity::Daily && count($group) === 1
                ? $group[0]
                : ReportRow::sum(new DateTimeImmutable($start), $group);
        }

        return $out;
    }

    private static function periodStart(DateTimeImmutable $date, ReportStatisticsGranularity $granularity): DateTimeImmutable
    {
        $year = (int) $date->format('Y');

        return match ($granularity) {
            ReportStatisticsGranularity::Weekly => $date->modify('monday this week'),
            ReportStatisticsGranularity::Monthly => $date->modify('first day of this month'),
            ReportStatisticsGranularity::Quarterly => $date->setDate($year, intdiv((int) $date->format('n') - 1, 3) * 3 + 1, 1),
            ReportStatisticsGranularity::Yearly => $date->setDate($year, 1, 1),
            default => $date,
        };
    }

    /**
     * Direct access to the low-level client for endpoints not yet wrapped.
     */
    public function client(): SklikClient
    {
        return $this->client;
    }
}
