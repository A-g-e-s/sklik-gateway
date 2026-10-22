<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Report;

use Ages\SklikGateway\Enum\ReportFileFormat;
use Ages\SklikGateway\Enum\ReportStatus;
use Ages\SklikGateway\Exception\SklikException;
use Ages\SklikGateway\Http\SklikClient;
use Ages\SklikGateway\Report\Response\ReportItem;
use Ages\SklikGateway\Report\Response\StreamedReport;

/**
 * Statistical report endpoints (/sklik/reports/).
 *
 * Reports (including Seznam Nákupy statistics reports) are generated
 * asynchronously. Create one via {@see \Ages\SklikGateway\Nakupy\NakupyApi},
 * then list/download it here once its status is ready.
 */
class ReportApi
{
    public function __construct(private readonly SklikClient $client)
    {
    }

    /**
     * List reports for the user (GET /sklik/reports/).
     *
     * @param list<int>|null $ids Restrict to specific report ids.
     * @return list<ReportItem>
     */
    public function listReports(
        ?string $order = null,
        ?int $offset = null,
        ?int $limit = null,
        ?string $type = null,
        ?array $ids = null,
    ): array {
        $data = $this->client->get('sklik/reports/', array_filter([
            'order' => $order,
            'offset' => $offset,
            'limit' => $limit,
            'type' => $type,
            'id' => $ids,
        ], static fn (mixed $v): bool => $v !== null));

        $out = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (is_array($item)) {
                $out[] = ReportItem::fromArray($item);
            }
        }

        return $out;
    }

    /**
     * Fetch a single report listing entry, or null when it does not exist.
     */
    public function getReportItem(int $reportId): ?ReportItem
    {
        return $this->listReports(ids: [$reportId])[0] ?? null;
    }

    /**
     * Poll the report until it is generated and download its contents.
     *
     * @param int $timeout      Max seconds to wait for the report to finish.
     * @param int $pollInterval Seconds between status checks.
     * @throws SklikException When the report fails, disappears or does not finish in time.
     */
    public function waitForReport(
        int $reportId,
        int $timeout = 60,
        int $pollInterval = 2,
        ReportFileFormat $format = ReportFileFormat::Json,
    ): StreamedReport {
        $deadline = time() + $timeout;

        while (true) {
            $status = $this->getReportItem($reportId)?->reportStatus();
            if ($status === null) {
                throw new SklikException("Sklik report {$reportId} not found.");
            }
            if ($status === ReportStatus::Finished) {
                return $this->getReport($reportId, $format);
            }
            if (!$status->isPending()) {
                throw new SklikException("Sklik report {$reportId} failed with status '{$status->value}'.");
            }
            if (time() >= $deadline) {
                throw new SklikException("Sklik report {$reportId} not finished within {$timeout} s.");
            }

            sleep(max(1, $pollInterval));
        }
    }

    /**
     * Download the contents of a generated report as JSON (GET /sklik/reports/{reportId}).
     */
    public function getReport(int $reportId, ReportFileFormat $format = ReportFileFormat::Json): StreamedReport
    {
        $data = $this->client->get("sklik/reports/{$reportId}", ['format' => $format->value]);

        return StreamedReport::fromArray($data);
    }
}
